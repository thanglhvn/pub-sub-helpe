<?php

namespace PubSubHelper\Event;

class JobProcessing
{
    /**
     * The job instance.
     *
     * @var \PubSubHelper\Queue\RabbitMQJob
     */
    public $job;

    /**
     * Create a new event instance.
     *
     * @param  \PubSubHelper\Queue\RabbitMQJob  $job
     * @return void
     */
    public function __construct($job)
    {
        $this->job = $job;
    }
}
