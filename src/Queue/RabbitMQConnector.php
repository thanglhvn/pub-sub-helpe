<?php
/**
 * Created by Nam Ngo.
 * User: apple
 * Date: 2019-10-29
 * Time: 10:55
 */

namespace PubSubHelper\Queue;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\AmqpMessage;
//use Interop\Amqp\AmqpConsumer;

use Enqueue\AmqpLib\AmqpConnectionFactory as EnqueueAmqpConnectionFactory;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\AmqpTools\DelayStrategyAware;

use PubSubHelper\Helper\Arr;
use PubSubHelper\CommonTrait\EventManagerTrait;
//use PubSubHelper\Event\WorkerStopping;

class RabbitMQConnector
{

    use EventManagerTrait;

    /**
     * @var array
     */
    public $config;

    /**
     * @var AmqpContext
     */
    protected $context;

    /**
     * Unique value for every request
     * @var string
     */
    protected $correlationId;

    /**
     * Combination key defined in config
     *
     * @var string
     */
    protected $exchangeCombination;


    public function __construct(array $config = [])
    {
        $this->config = $config;
    }


    /**
     * Establish a RabbitMQ connection.
     * @param array $config
     * @throws \ReflectionException|\Exception
     */
    public function connect(array $config = [])
    {
        if (empty($config))
            $config = $this->config;

        if (empty($config))
            throw new \Exception('config not found');


        $factoryClass = Arr::get($config, 'factoryClass', EnqueueAmqpConnectionFactory::class);

        if (!class_exists($factoryClass) || !(new \ReflectionClass($factoryClass))->implementsInterface(InteropAmqpConnectionFactory::class)) {
            throw new \LogicException(sprintf('The factory_class option has to be valid class that implements "%s"', InteropAmqpConnectionFactory::class));
        }

        $connectConfig = Arr::get($config, 'connection.default');

        /** @var AmqpConnectionFactory $factory */
        $factory = new $factoryClass([
//            'dsn'            => Arr::get($connectConfig, 'dsn'),
            'host'           => Arr::get($connectConfig, 'host', '127.0.0.1'),
            'port'           => Arr::get($connectConfig, 'port', 5672),
            'user'           => Arr::get($connectConfig, 'username', 'guest'),
            'pass'           => Arr::get($connectConfig, 'password', 'guest'),
            'vhost'          => Arr::get($connectConfig, 'vhost', '/'),
            'ssl_on'         => Arr::get($connectConfig, 'ssl_params.ssl_on', false),
            'ssl_verify'     => Arr::get($connectConfig, 'ssl_params.verify_peer', true),
            'ssl_cacert'     => Arr::get($connectConfig, 'ssl_params.cafile'),
            'ssl_cert'       => Arr::get($connectConfig, 'ssl_params.local_cert'),
            'ssl_key'        => Arr::get($connectConfig, 'ssl_params.local_key'),
            'ssl_passphrase' => Arr::get($connectConfig, 'ssl_params.passphrase'),
        ]);

        if ($factory instanceof DelayStrategyAware)
            $factory->setDelayStrategy(new RabbitMqDlxDelayStrategy());

        $this->config  = $config;
        $this->context = $factory->createContext();

//        if (!empty($this->getEventManager()))
//            $this->getEventManager()->attach(WorkerStopping::class, function() {
//                $this->getContext()->close();
//            });
    }

    /**
     * @param string   $exchangeCombination
     * @param callable $callback
     * @throws \Interop\Queue\Exception\SubscriptionConsumerNotSupportedException
     * @throws \Interop\Queue\Exception\TemporaryQueueNotSupportedException
     */
    public function consume(string $exchangeCombination, callable $callback)
    {
        [$queue] = $this->declareEverything($exchangeCombination);
        $consumer                  = $this->context->createConsumer($queue);
        $this->exchangeCombination = $exchangeCombination;

        // subscribe to the queue
        $subscriptionConsumer = $this->context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);

        $subscriptionConsumer->consume();
    }


    /**
     * Publish a message to exchange
     *
     * @param        $payload
     * @param string $exchangeCombination
     * @param array  $options
     * @return string|null
     * @throws \Interop\Queue\Exception
     */
    public function publish($payload, string $exchangeCombination, array $options = [])
    {
        $data = [
            'payload' => $payload
        ];

        // remove attempts options
        unset($options['attempts']);

        return $this->pushRaw($data, $exchangeCombination, $options);
    }

    /**
     * Publish a message and then close the connection
     *
     * @param        $payload
     * @param string $exchangeCombination
     * @param array  $options
     * @return string|null
     * @throws \Interop\Queue\Exception
     * @throws \ReflectionException
     */
    public function publishAndClose($payload, string $exchangeCombination, array $options = [])
    {
        if (empty($this->getContext()))
            $this->connect();

        $correlationId = $this->publish($payload, $exchangeCombination, $options);

        $this->closeConnection();

        return $correlationId;
    }


    /**
     * @param       $payload
     * @param null  $exchangeCombination
     * @param array $options = [
     *     'properties' => [
     *          'x-signature' => (string)
     *     ],
     *     'routing_key' => (string),
     *     'priority' => (int),
     *     'expiration' => (int),
     *     'headers' => ([]),
     *     'properties' => ([]),
     *     'attempts' => (int),
     *     'delay' => (int) // in second
     *
     * ]
     * @return string|null
     * @throws \Interop\Queue\Exception
     */
    public function pushRaw($payload, $exchangeCombination = null, array $options = [])
    {
        try {
            /**
             * @var AmqpTopic
             * @var AmqpQueue $queue
             */
            [$queue, $topic] = $this->declareEverything($exchangeCombination);

            /** @var AmqpMessage $message */
            $encodedMsg = json_encode($payload);
            $message = $this->context->createMessage($encodedMsg);

            // sign the data
            if (empty($options['properties']['x-signature']))
                $options['properties']['x-signature'] = hash('sha256', $encodedMsg);

            unset($encodedMsg);

            $message->setCorrelationId($this->getCorrelationId());
            $message->setContentType('application/json');
            $message->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);


            if (isset($options['routing_key']))
                $message->setRoutingKey($options['routing_key']);
            else
                $message->setRoutingKey($queue->getQueueName());


            if (isset($options['priority']))
                $message->setPriority($options['priority']);


            if (isset($options['expiration']))
                $message->setExpiration($options['expiration']);


            if (isset($options['headers']))
                $message->setHeaders($options['headers']);


            if (isset($options['properties']))
                $message->setProperties($options['properties']);



            if (isset($options['attempts']))
                $message->setProperty(RabbitMQJob::ATTEMPT_COUNT_HEADERS_KEY, $options['attempts']);


            $producer = $this->context->createProducer();
            if (isset($options['delay']) && $options['delay'] > 0)
                $producer->setDeliveryDelay($options['delay'] * 1000);


            $producer->send($topic, $message);

            return $message->getCorrelationId();
        } catch (\Exception $exception) {
//            $this->reportConnectionError('pushRaw', $exception);

            var_dump($exception->getMessage());
            return null;
        }
    }

    /**
     * @param string|null $exchangeCombination
     * @return array
     * @throws \Interop\Queue\Exception\TemporaryQueueNotSupportedException|\Exception
     */
    public function declareEverything(string $exchangeCombination = null): array
    {
        $combinationConfig = Arr::get($this->config, 'exchange_combination.'.$exchangeCombination, false);

        if (empty($combinationConfig) || empty($combinationConfig['exchange']) || empty($combinationConfig['queue']))
            throw new \Exception('combination name not found');

        $queueConfig    = Arr::get($combinationConfig, 'queue');
        $exchangeConfig = Arr::get($combinationConfig, 'exchange');


        // generate an exchange from config
        $topic = $this->context->createTopic($exchangeConfig['name']);
        $topic->setType($exchangeConfig['type']);
        // we temporary dont have extra arguments
//        $topic->setArguments($exchangeConfig['arguments']);

        if ($exchangeConfig['passive'])
            $topic->addFlag(AmqpTopic::FLAG_PASSIVE);

        if ($exchangeConfig['durable'])
            $topic->addFlag(AmqpTopic::FLAG_DURABLE);

        if ($exchangeConfig['auto_delete'])
            $topic->addFlag(AmqpTopic::FLAG_AUTODELETE);


//        if ($this->exchangeOptions['declare'] && ! in_array($exchangeName, $this->declaredExchanges, true)) {
//            $this->context->declareTopic($topic);
//
//            $this->declaredExchanges[] = $exchangeName;
//        }

        if (empty($queueConfig['name']))
            $queue = $this->context->createTemporaryQueue();
        else
            $queue = $this->context->createQueue($queueConfig['name']);

        if ($queueConfig['passive'])
            $queue->addFlag(AmqpQueue::FLAG_PASSIVE);

        if ($queueConfig['durable'])
            $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        if ($queueConfig['exclusive'])
            $queue->addFlag(AmqpQueue::FLAG_EXCLUSIVE);

        if ($queueConfig['auto_delete'])
            $queue->addFlag(AmqpQueue::FLAG_AUTODELETE);


        // bind the queue to the exchange
        $this->context->bind(
            new AmqpBind($queue, $topic, Arr::get($combinationConfig, 'route'))
        );

        return [$queue, $topic];
    }

    /**
     * Close the connection to queue
     */
    public function closeConnection()
    {
        $this->getContext()->close();
    }

    /**
     * Get Context
     *
     * @return AmqpContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Retrieves the correlation id, or a unique id.
     *
     * @return string
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId ?: uniqid('', true);
    }

    /**
     * Sets the correlation id for a message to be published.
     *
     * @param string $id
     *
     * @return void
     */
    public function setCorrelationId(string $id): void
    {
        $this->correlationId = $id;
    }

    public function getExchangeCombination(): string
    {
        return $this->exchangeCombination;
    }
}