<?php
/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-11-06
 * Time: 14:18
 */

namespace PubSubHelper\CommonTrait;

use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;

trait EventManagerTrait
{
    /**
     * @var \Laminas\EventManager\EventManagerInterface;
     */
    protected $eventManager;

    protected $sharedEventManager;

    public function setEventManager(EventManagerInterface $event)
    {
        $this->eventManager = $event;
    }

    public function getEventManager()
    {
        return $this->eventManager;
    }

    public function getSharedEventManager()
    {
        if (empty($this->sharedEventManager)) {
            $this->sharedEventManager = new SharedEventManager();
        }

        return $this->sharedEventManager;
    }
}