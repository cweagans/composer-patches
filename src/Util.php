<?php

namespace cweagans\Composer;

class Util
{
    /**
     * Recursively merge arrays without changing data types of values.
     *
     * Does not change the data types of the values in the arrays. Matching keys'
     * values in the second array overwrite those in the first array, as is the
     * case with array_merge.
     *
     * @param array $array1
     *   The first array.
     * @param array $array2
     *   The second array.
     * @return array
     *   The merged array.
     *
     * @see http://php.net/manual/en/function.array-merge-recursive.php#92195
     */
    public static function arrayMergeRecursiveDistinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
