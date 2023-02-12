<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use cweagans\Composer\Event\PatchEvent;
use cweagans\Composer\Event\PatchEvents;
use cweagans\Composer\Patch;

class PatchEventTest extends Unit
{
    /**
     * Tests all the getters.
     *
     * @dataProvider patchEventDataProvider
     */
    public function testGetters($event_name, $patch)
    {
        $composer = new Composer();
        $io = new NullIO();

        $patch_event = new PatchEvent($event_name, $patch, $composer, $io);
        $this->assertEquals($event_name, $patch_event->getName());
        $this->assertEquals($patch, $patch_event->getPatch());
    }

    public function patchEventDataProvider()
    {
        $patch = new Patch();

        return array(
            [PatchEvents::PRE_PATCH_GUESS_DEPTH, $patch],
            [PatchEvents::PRE_PATCH_APPLY, $patch],
            [PatchEvents::POST_PATCH_APPLY, $patch],
            [PatchEvents::PRE_PATCH_DOWNLOAD, $patch],
            [PatchEvents::POST_PATCH_DOWNLOAD, $patch],
        );
    }
}
