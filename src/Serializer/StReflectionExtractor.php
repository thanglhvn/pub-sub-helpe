<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2019-10-18
 * Time: 16:50
 */

namespace PubSubHelper\Serializer;

use \Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

use Symfony\Component\PropertyInfo\Type;
use StCommonService\Helper\Arr;

/**
 * Class StReflectionExtractor
 *
 * Our custom ReflectionExtract built on top of symfony's plugin
 * This will transform types of target property to the desired type
 *
 * @package StQueue\Serializer
 */
class StReflectionExtractor extends ReflectionExtractor
{
    protected const MAP_TYPES = [
        'integer' => Type::BUILTIN_TYPE_INT,
        'boolean' => Type::BUILTIN_TYPE_BOOL,
        'double'  => Type::BUILTIN_TYPE_FLOAT,
    ];

    private $_transformList = [];


    public function getTypes($class, $property, array $context = []): ?array
    {

        // Before get the REAL type of that property
        // we check if that property exist in the transformList
        if (Arr::exists($property, $this->_transformList)) {
            $referenceClassPath = $this->_transformList[$property];

            // we transform the exist type to the desired type
            // by getting the type of the alternative reference class
            return parent::getTypes($referenceClassPath, 'data', $context);
        }

        // if the property is not in the list
        // execute the function as normal
        return parent::getTypes($class, $property, $context);
    }

    public function setTransformList(array $transformList = [])
    {
        $this->_transformList = $transformList;
    }

}