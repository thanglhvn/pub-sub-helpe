<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 2020-02-05
 * Time: 15:13
 */

namespace PubSubHelper\Helper;


class Arr
{

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param array $array
     * @param string|int|array $key
     * @return bool
     */
    public static function exists($key, array $array)
    {
        if (is_string($key) || is_int($key))
            return array_key_exists($key, $array);
        elseif (is_array($key))
            return !array_diff_key(array_flip($key), $array);
        else
            return null;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param array $array
     * @param string $key
     * @param null $default
     * @return array|mixed|null
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (is_null($key))
            return $array;

        if (static::exists($key, $array))
            return $array[$key];

        if (strpos($key, '.') === false)
            return $array[$key] ?? $default;

        foreach (explode('.', $key) as $segment) {
            if (static::exists($segment, $array))
                $array = $array[$segment];
            else
                return null;
        }

        return $array;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse(array $array)
    {
        $results = [];
        foreach ($array as $values) {
            if (! is_array($values)) {
                continue;
            }
            $results = array_merge($results, $values);
        }

        return $results;
    }

}