<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Package\Package;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchEvent;
use cweagans\Composer\PatchEvents;
use Composer\Package\PackageInterface;

class PatchEventTest extends Unit
{
    /**
     * Tests all the getters.
     *
     * @dataProvider patchEventDataProvider
     */
    public function testGetters($event_name, PackageInterface $package, $patch)
    {
        $patch_event = new PatchEvent($event_name, $package, $patch);
        $this->assertEquals($event_name, $patch_event->getName());
        $this->assertEquals($package, $patch_event->getPackage());
        $this->assertEquals($patch, $patch_event->getPatch());
    }

    public function patchEventDataProvider()
    {
        $package = new Package('drupal/drupal', '1.0.0.0', '1.0.0');
        $patch = new Patch();

        return array(
            array(PatchEvents::PRE_PATCH_APPLY, $package, $patch),
            array(PatchEvents::POST_PATCH_APPLY, $package, $patch),
            array(PatchEvents::PRE_PATCH_DOWNLOAD, $package, $patch),
            array(PatchEvents::POST_PATCH_DOWNLOAD, $package, $patch),
        );
    }
}
