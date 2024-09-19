<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-12-23
 * Time: 11:32
 */

namespace PubSubHelper;

use PubSubHelper\Publisher\AbstractPublisher;

abstract class MainPublisher extends AbstractPublisher
{

    /**
     * Custom Publisher must extend the handle function it own
     * The method will be called after the command is dispatched
     *
     * @return mixed
     */
    abstract public function handle();


    /**
     * Dispatch the command
     *
     * @return MainPublisher
     */
    public static function dispatch()
    {
        // init the class and put in what it needs
        $self = new static(...func_get_args());
        $self->handle();

        return $self;
    }
}