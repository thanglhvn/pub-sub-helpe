<?php
/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-10-15
 * Time: 15:06
 */

namespace PubSubHelper\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

class StPropertyNormalizer extends PropertyNormalizer
{

    /**
     * Create child context and also add the attribute name to
     * identify which field we are process in Normalizer class
     *
     * @param array  $parentContext
     * @param string $attribute
     * @return array
     */
    protected function createChildContext(array $parentContext, $attribute, ?string $format): array
    {
        $childContext =  parent::createChildContext($parentContext, $attribute, $format);
        $childContext['attribute'] = $attribute;

        return $childContext;
    }

}