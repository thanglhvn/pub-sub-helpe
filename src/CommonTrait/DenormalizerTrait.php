<?php
/**
 * Created by PhpStorm.
 * User: Nam Ngo
 * Date: 2019-10-30
 * Time: 14:45
 */

namespace PubSubHelper\CommonTrait;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
//use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
//use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
//use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Serializer;

use PubSubHelper\Serializer\Normalizer\StPropertyNormalizer;
use PubSubHelper\Serializer\NameConverter\PropertyNameConverter;
use PubSubHelper\Serializer\StReflectionExtractor;

trait DenormalizerTrait {

    abstract public function getEntityClass(array $context = []): string;

    /**
     * @param array $opt Option to replace all the getter functions, in case you want to
     *                   denormalize a part of data. All the config keys are:
     *
     *                  normalizers: an array of normalizer instance will be used
     *                  encoders: an array of encoders will be used
     *                  entityClass: string path of an entity class
     *                  format: format type in string
     *                  denormalizeContext: array of context for denormalization
     *
     * @return array|object Option for denormalize
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface|\Exception
     */
    final protected function denormalize(array $opt = [])
    {
        if (empty($opt['data']))
            throw new \Exception('data_not_found_for_denormalize');


        $normalizedData = $this->prepareForDenormalization($opt);

        // release the data key
        unset($opt['data']);

        // validate the data type transform list
        // TODO: handle the option

        $serializer = new Serializer(
        $opt['normalizers'] ?? $this->getNormalizers($opt),
        $opt['encoders'] ?? $this->getEncoders($opt)
        );

        return $serializer->denormalize(
            $normalizedData,
            $opt['entityClass'] ?? $this->getEntityClass($opt),
            $opt['format'] ?? 'json',
            $opt['denormalizeContext'] ?? $this->getDenormalizeContext($opt)
        );
    }

    protected function prepareForDenormalization(array $opt): array
    {
        $data = $opt['data'];


        return (array) $data;
    }

    /**
     * @param array $context
     * @return array
     */
    protected function getNormalizers(array $context): array
    {

        $extractor = new StReflectionExtractor();

        // transform data's type to desired one
        if (!empty($context['dataType']) && is_array($context['dataType']))
            $extractor->setTransformList($context['dataType']);

        return [

            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY   => \Datetime::RFC3339_EXTENDED,
                DateTimeNormalizer::TIMEZONE_KEY => new \DateTimeZone('Asia/Ho_Chi_Minh'),
            ]),
            new StPropertyNormalizer(
                null,
                new PropertyNameConverter($this->getPropertyNameConvertMap()),
                $extractor
            ),
            //            new ObjectNormalizer(
            //                null, new PropertyNameConverter($this->getPropertyNameConvertMap()), null,
            //                new ReflectionExtractor(), null, null
            //            ),
            //            new PropertyNormalizer(null, new PropertyNameConverter($this->getPropertyNameConvertMap()), new ReflectionExtractor()),
        ];
    }

    /**
     * Returns an array of properties, which will be converted to
     *
     * @return array {Alias} => {Desired Property Name}
     */
    protected function getPropertyNameConvertMap(): array
    {
        return [];
    }

    /**
     * @param array $context
     * @return array
     */
    protected function getEncoders(array $context): array
    {
        return [];
    }

    /**
     * Get method of Symfony Deserialize's context
     *
     * @param array $context
     * @return array
     */
    protected function getDenormalizeContext(array $context): array
    {
        return [
            AbstractNormalizer::OBJECT_TO_POPULATE            => true,
            AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            // by default ID field will be ignored
            // (ID of a row is auto-increment, cant set it directly through setId)
            AbstractNormalizer::IGNORED_ATTRIBUTES => [
                'id'
            ]
        ];
    }
}