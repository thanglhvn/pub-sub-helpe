<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 11/20/20
 * Time: 2:51 PM
 */

namespace PubSubHelper\Publisher;


use PubSubHelper\Helper\Arr;

class SimplePublisher extends AbstractPublisher
{

    /**
     * Dispatch the message and return itself
     * Dev must close the connection manually
     *
     * @param array|int|bool $payload Message's payload data
     * @param array          $queueConfig
     * @param array          $messageConfig  = [
     *     'exchange_name' => (string), //required to specify the exchange name in config
     *     'properties' => [
     *          'x-signature' => (string)
     *     ],
     *     'routing_key' => (string),
     *     'priority' => (int),
     *     'expiration' => (int),
     *     'headers' => ([]),
     *     'properties' => ([]),
     *     'attempts' => (int),
     *     'delay' => (int), // in second
     * ]
     *
     * @return static
     * @throws \ReflectionException|\Exception
     * @throws \Interop\Queue\Exception
     */
    public static function dispatch($payload, array $queueConfig, array $messageConfig)
    {
        $self = new static($queueConfig);

        // open the connection immediately
        // because of the type of this publisher: "Simple"
        $self->openConnection();

        // trigger the main function "handle"
        $self->handle($payload, $messageConfig);

        return $self;
    }

    /**
     * Dispatch the message then destroy the connection to the Queue
     *
     * @param       $payload
     * @param array $queueConfig
     * @param array $messageConfig = $messageConfig
     * @throws \ReflectionException
     * @throws \Interop\Queue\Exception
     */
    public static function dispatchDestroy($payload, array $queueConfig, array $messageConfig)
    {
        self::dispatch($payload, $queueConfig, $messageConfig)
            ->closeConnection()
        ;
    }

    /**
     * Publish the payload to the given config when init
     *
     * @param $payload
     * @param array $messageConfig = $messageConfig
     * @throws \Interop\Queue\Exception|\Exception
     */
    public function handle($payload, array $messageConfig)
    {
        if (!Arr::exists('exchange_name', $messageConfig))
            throw new \Exception('Option value "exchange_name" is required');

        $exchangeCombination = $messageConfig['exchange_name'];
        unset($messageConfig['exchange_name']);

        $this->getConnector()->publish(
            $payload,
            $exchangeCombination,
            $messageConfig
        );
    }
}