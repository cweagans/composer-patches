<?php

/**
 * @file
 * Tests the Util class methods.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use cweagans\Composer\Util;

class UtilTest extends Unit
{
    /**
     * Tests arrayMergeRecursiveDistinct.
     *
     * @dataProvider arrayMergeRecursiveDistinctDataProvider
     */
    public function testArrayMergeRecursiveDistinct($array1, $array2, $expected)
    {
        $merged = Util::arrayMergeRecursiveDistinct($array1, $array2);
        $this->assertEquals($expected, $merged);
    }

    public function arrayMergeRecursiveDistinctDataProvider()
    {
        return [
            // Empty arrays.
            [
                [],
                [],
                []
            ],
            // Simple arrays with different keys.
            [
                ['key1' => 'foo'],
                ['key2' => 'bar'],
                ['key1' => 'foo', 'key2' => 'bar'],
            ],
            // Simple arrays with same keys.
            [
                ['key1' => 'foo'],
                ['key1' => 'bar'],
                ['key1' => 'bar'],
            ],
            // Deep arrays with different keys
            [
                [
                    'project' => [
                        'key1' => 'bar',
                        'key2' => 'foo'
                    ],
                ],
                [
                    'project2' => [
                        'key1' => 'foo',
                    ],
                ],
                [
                    'project' => [
                        'key1' => 'bar',
                        'key2' => 'foo'
                    ],
                    'project2' => [
                        'key1' => 'foo',
                    ],
                ],
            ],
            // Deep arrays with same keys
            [
                [
                    'project' => [
                        'key1' => 'bar',
                        'key2' => 'foo'
                    ],
                ],
                [
                    'project' => [
                        'key1' => 'foo',
                    ],
                ],
                [
                    'project' => [
                        'key1' => 'foo',
                        'key2' => 'foo'
                    ],
                ],
            ],
        ];
    }
}
