<?php

/**
 * @file
 * Test the Patches plugin class.
 */

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use cweagans\Composer\Plugin\Patches;

class PatchesPluginTest extends Unit
{
    /**
     * Test plugin activation.
     *
     * Despite not actually asserting anything, this test ensures that if the
     * plugin is required and not configured (through any means of doing so),
     * it won't cause an exception to be thrown or something.
     */
    public function testActivate()
    {
        $plugin = new Patches();
        $composer = new Composer();
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([]);
        $composer->setPackage($package);
        $io = new NullIO();
        $plugin->activate($composer, $io);
    }
}
