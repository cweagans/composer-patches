<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests;

use Composer\Composer;
use Composer\IO\NullIO;
use Codeception\Test\Unit;
use Composer\Package\Package;
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
    public function testGetters(
        $event_name,
        Composer $composer,
        NullIO $io,
        PackageInterface $package,
        $url,
        $description,
        \Exception $error = null
    ){
        $patch_event = new PatchEvent($event_name, $composer, $io, $package, $url, $description, $error);
        $this->assertEquals($event_name, $patch_event->getName());
        $this->assertEquals($composer, $patch_event->getComposer());
        $this->assertEquals($io, $patch_event->getIO());
        $this->assertEquals($package, $patch_event->getPackage());
        $this->assertEquals($url, $patch_event->getUrl());
        $this->assertEquals($description, $patch_event->getDescription());
        $this->assertEquals($error, $patch_event->getError());
    }

    public function patchEventDataProvider()
    {
        $io = new NullIO();
        $composer = new Composer();
        $error = new \Exception("Cannot apply patch https://www.drupal.org");
        $package = new Package('drupal/drupal', '1.0.0.0', '1.0.0');

        return array(
            array(PatchEvents::PRE_PATCH_APPLY, $composer, $io, $package, 'https://www.drupal.org', 'A test patch'),
            array(PatchEvents::POST_PATCH_APPLY, $composer, $io, $package, 'https://www.drupal.org', 'A test patch'),
            array(PatchEvents::PATCH_APPLY_ERROR, $composer, $io, $package, 'https://www.drupal.org', 'A test patch that fails', $error)
        );
    }
}
