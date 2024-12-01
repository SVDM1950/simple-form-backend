<?php

namespace App\Helper;

class ArrayUtils
{
    /**
     * @param callable $callback
     * @param array    $array
     *
     * @return array
     */
    public static function map(callable $callback, array $array): array
    {
        $keys = array_keys($array);
        $items = array_map($callback, array_keys($array), array_values($array));

        return array_combine($keys, $items) ;
    }

    /**
     * @param array    $input
     * @param callable $callback
     *
     * @return int|string|null
     */
    public static function findFirstIndex(array $input, callable $callback): int|string|null
    {
        $result = array_keys(array_filter($input, $callback));

        if ($result === []) {
            return null;
        }

        return array_shift($result);
    }

    /**
     * @param array    $input
     * @param callable $callback
     *
     * @return mixed
     */
    public static function findFirst(array $input, callable $callback): mixed
    {
        $result = array_filter($input, $callback);

        if ($result === []) {
            return null;
        }

        return array_shift($result);
    }

    /**
     * @param array    $input
     * @param callable $callback
     *
     * @return array
     */
    public static function sort(array $input, callable $callback): array
    {
        usort($input, $callback);
        return $input;
    }

    /**
     * @param array    $input
     * @param callable $callback
     *
     * @return bool
     */
    public static function some(array $input, callable $callback): bool
    {
        return count(array_filter($input, $callback)) > 0;
    }

    /**
     * @param array    $input
     * @param callable $callback
     * @return bool
     */
    public static function every(array $input, callable $callback): bool
    {
        return count(array_filter($input, $callback)) === count($input);
    }
}
