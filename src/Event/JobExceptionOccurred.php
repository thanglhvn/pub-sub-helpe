<?php

namespace PubSubHelper\Event;

class JobExceptionOccurred
{

    /**
     * The job instance.
     *
     * @var \PubSubHelper\Queue\RabbitMQJob
     */
    public $job;

    /**
     * The exception instance.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  \PubSubHelper\Queue\RabbitMQJob  $job
     * @param  \Exception  $exception
     * @return void
     */
    public function __construct($job, $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
    }
}
