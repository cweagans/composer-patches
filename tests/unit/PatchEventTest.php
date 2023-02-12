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
use Exception;

class PatchEventTest extends Unit
{
    /**
     * Tests all the getters.
     *
     * @dataProvider patchEventDataProvider
     */
    public function testGetters($event_name, $patch, $composer, $io, $error = null)
    {
        $patch_event = new PatchEvent($event_name, $patch, $composer, $io, $error);
        $this->assertEquals($event_name, $patch_event->getName());
        $this->assertEquals($patch, $patch_event->getPatch());
        $this->assertEquals($composer, $patch_event->getComposer());
        $this->assertEquals($io, $patch_event->getIO());
        $this->assertEquals($error, $patch_event->getError());
    }

    public function patchEventDataProvider()
    {
        $patch = new Patch();
        $composer = new Composer();
        $io = new NullIO();
        $e = new Exception("test");

        return array(
            [PatchEvents::PRE_PATCH_GUESS_DEPTH, $patch, $composer, $io],
            [PatchEvents::PRE_PATCH_APPLY, $patch, $composer, $io],
            [PatchEvents::POST_PATCH_APPLY, $patch, $composer, $io],
            [PatchEvents::PRE_PATCH_DOWNLOAD, $patch, $composer, $io],
            [PatchEvents::POST_PATCH_DOWNLOAD, $patch, $composer, $io],
            [PatchEvents::POST_PATCH_APPLY_ERROR, $patch, $composer, $io, $e],
        );
    }
}
