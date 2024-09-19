<?php
/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-10-31
 * Time: 17:07
 */

namespace PubSubHelper\Queue;

use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpMessage;
//use PubSubHelper\CommonTrait;

use PubSubHelper\Helper\Arr;

/**
 * Class RabbitMQJob
 * Custom Helper class to provide helpful methods
 * to handle queue message
 *
 */
class RabbitMQJob
{
//    use CommonTrait\EventManagerTrait;

    /**
     * Same as RabbitMQQueue, used for attempt counts.
     */
    public const ATTEMPT_COUNT_HEADERS_KEY = 'attempts_count';

    /**
     * Queue name
     * @var string
     */
    protected $queue;

    /**
     * Chosen exchange combination in config file
     *
     * @var string
     */
    protected $exchangeCombination;

    /**
     * @var RabbitMQConnector
     */
    protected $connection;

    /**
     * @var AmqpConsumer
     */
    protected $consumer;

    /**
     * @var AmqpMessage
     */
    protected $message;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected $failed = false;

    /**
     * @var bool
     */
    protected $parsedBody = false;



    public function __construct(
        RabbitMQConnector $connection,
        AmqpConsumer $consumer,
        AmqpMessage $message
    ) {
        $this->connection = $connection;
        $this->consumer = $consumer;
        $this->message = $message;

        $this->queue               = $consumer->getQueue()->getQueueName();
        $this->exchangeCombination = $connection->getExchangeCombination();
//        $this->connectionName = $connection->getConnectionName();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->message->getBody();
    }

    /**
     * Get parsed body message
     *
     * @return mixed
     */
    public function getBody()
    {
        if ($this->parsedBody === false)
            $this->parsedBody = json_decode(
                $this->getRawBody(),
                true
            );

        return $this->parsedBody;
    }


    /**
     * Get the payload in message
     *
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getPayload(string $key = null)
    {
        $key = 'payload' . ($key ? '.' . $key : '');

        return Arr::get($this->getBody(), $key);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->consumer->acknowledge($this->message);
        $this->deleted = true;
    }

    /**
     * Release the job back into the queue. By acknowledging the current message,
     * then create a clone of it (with extra properties - redelivered, attemps, etc)
     * and send it back to the queue.
     * RabbitMQ allows us to nack the message and re-queue it automatically, but does not
     * allow us to change the message
     *
     * @param int $delay
     * @return string|null
     * @throws \Interop\Queue\Exception
     */
    public function release($delay = 0)
    {

        // firstly, delete the message
        $this->delete();
        $this->released = true;

        // secondly, compose a clone of it
        $body = $this->getBody();
//        $body['payload']['id'] = 1;


        return $this->connection->pushRaw($body, $this->connection->getExchangeCombination(), [
            //            'delay' => $this->secondsUntil($delay),
            'delay'       => 0,
            'attempts'    => $this->getAttempts() + 1,
            'routing_key' => $this->message->getRoutingKey(),
//            'properties' => []
        ]);
    }

    /**
     * Delete the job
     *
     * @return void
     */
    public function fail()
    {
        $this->markAsFailed();

        if ($this->isDeleted())
            return;

        $this->delete();

    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed()
    {
        return $this->failed;
    }

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed()
    {
        $this->failed = true;
    }

    /**
     * Get the job identifier.
     *
     * @return string|null
     */
    public function getJobId(): ?string
    {
        return $this->message->getCorrelationId();
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        // set default job attempts to 1 so that jobs can run without retry
        $defaultAttempts = 1;

        return $this->message->getProperty(self::ATTEMPT_COUNT_HEADERS_KEY, $defaultAttempts);
    }

    /**
     * Get routing key from this message
     *
     * @return string|null
     */
    public function getRoutingKey(): ?string
    {
        return $this->message->getRoutingKey();
    }
}