<?php

namespace PubSubHelper\CommonTrait;

/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-10-28
 * Time: 17:52
 */
trait SoapPluginTrait
{

    private $_soapPlugin;

    public function setSoapPlugin($soapPlugin)
    {
        $this->_soapPlugin = $soapPlugin;
    }

    public function getSoapPlugin()
    {
        return $this->_soapPlugin;
    }
}