<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 11/20/20
 * Time: 3:10 PM
 */

namespace PubSubHelper\Publisher;

use PubSubHelper\Queue\RabbitMQConnector;

abstract class AbstractPublisher
{
    /**
     * Queue config
     * @var array
     */
    protected $config;

    /**
     * @var RabbitMQConnector
     */
    protected $connector;


    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connector = new RabbitMQConnector($config);
    }

    /**
     * @return RabbitMQConnector
     */
    public function getConnector(): RabbitMQConnector
    {
        return $this->connector;
    }

    /**
     * @param RabbitMQConnector $connector
     */
    public function setConnector(RabbitMQConnector $connector): void
    {
        $this->connector = $connector;
    }

    /**
     * Open the connection
     *
     * @param array $config null
     * @throws \ReflectionException
     */
    public function openConnection(array $config = []): void
    {
        $this->getConnector() ? $this->getConnector()->connect($config) : null;
    }

    /**
     * Close the current connection
     */
    public function closeConnection(): void
    {
        $this->getConnector() ? $this->getConnector()->closeConnection() : null;
    }
}