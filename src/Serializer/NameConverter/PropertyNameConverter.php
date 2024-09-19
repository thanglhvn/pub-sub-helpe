<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-10-16
 * Time: 15:30
 */

namespace PubSubHelper\Serializer\NameConverter;

use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;

use StCommonService\Helper\Arr;

class PropertyNameConverter implements AdvancedNameConverterInterface
{

    /**
     * Field's Name will be converted
     * {Alias} => {Desired Name}
     *
     * @var array
     */
    private $_maps;

    public function __construct(array $maps = [])
    {
        $this->_maps = $maps;
    }

    public function normalize($propertyName, string $class = null, string $format = null, array $context = [])
    {
        return $this->_convertName($propertyName);
    }

    public function denormalize($propertyName, string $class = null, string $format = null, array $context = [])
    {
        return $this->_convertName($propertyName);
    }


    private function _convertName(string $propertyName): string
    {
        if (!Arr::exists($propertyName, $this->_maps))
            return $propertyName;

        return Arr::get($this->_maps, $propertyName);
    }
}