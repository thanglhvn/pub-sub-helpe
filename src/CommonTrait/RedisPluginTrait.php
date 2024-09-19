<?php

namespace PubSubHelper\CommonTrait;

/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-10-28
 * Time: 17:52
 */
trait RedisPluginTrait
{

    private $_redisPlugin;

    public function setRedisPlugin($redisPlugin)
    {
        $this->_redisPlugin = $redisPlugin;
    }

    public function getRedisPlugin()
    {
        return $this->_redisPlugin;
    }
}