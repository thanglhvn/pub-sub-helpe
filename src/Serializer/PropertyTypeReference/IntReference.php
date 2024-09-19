<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-10-18
 * Time: 18:27
 */

namespace PubSubHelper\Serializer\PropertyTypeReference;


class IntReference
{
    /**
     * @var int
     */
    private $_data;

    /**
     * @return int
     */
    public function getData(): int
    {
        return $this->_data;
    }

    /**
     * @param int $_data
     */
    public function setData(int $_data): void
    {
        $this->_data = $_data;
    }
}