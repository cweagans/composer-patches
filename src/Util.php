<?php

namespace cweagans\Composer;

class Util
{
    /**
     * Contains pre-defined patch depths for various composer packages.
     *
     * You can open a pull request to add to this list. These values are *only* used
     * if there is no patch depth specified on the patch itself (in composer.json
     * or your patches file). Otherwise, the plugin will default to a depth of 1
     * (-p1 as an argument to git or patch).
     */
    public static function getDefaultPackagePatchDepth($package): ?int
    {
        // You can open a pull request to add to this list.
        // Please keep it alphabetized by vendor, then package name.
        $packages = [
            'drupal/core' => 2,
        ];

        return $packages[$package] ?? null;
    }

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
    public static function arrayMergeRecursiveDistinct(array $array1, array $array2): array
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
