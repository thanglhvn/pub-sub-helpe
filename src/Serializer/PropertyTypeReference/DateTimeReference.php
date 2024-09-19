<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-10-18
 * Time: 11:35
 */

namespace PubSubHelper\Serializer\PropertyTypeReference;


class DateTimeReference
{
    /**
     * @var \DateTimeInterface
     */
    private $_data;

    /**
     * @return \DateTimeInterface
     */
    public function getData(): \DateTimeInterface
    {
        return $this->_data;
    }

    /**
     * @param \DateTimeInterface|null $data
     */
    public function setData(\DateTimeInterface $data = null): void
    {
        $this->_data = $data;
    }

}