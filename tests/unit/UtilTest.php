<?php

/**
 * @file
 * Tests the Util class methods.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use cweagans\Composer\Util;

class UtilTest extends Unit
{
    /**
     * Tests getDefaultPackagePatchDepth.
     */
    public function testGetDefaultPackagePatchDepth()
    {
        $this->assertEquals(2, Util::getDefaultPackagePatchDepth('drupal/core'));
        $this->assertNull(Util::getDefaultPackagePatchDepth('not-a-real-package'));
    }
}
