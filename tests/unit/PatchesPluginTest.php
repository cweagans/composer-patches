<?php

/**
 * @file
 * Test the Patches plugin class.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use cweagans\Composer\Patch;
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

    /**
     * Test the patch depth settings.
     *
     * @dataProvider guessDepthDataProvider
     */
    public function testGuessDepth(Patches $plugin, Patch $patch, int $expectedDepth)
    {
        $plugin->guessDepth($patch);
        $this->assertEquals($expectedDepth, $patch->depth);
    }

    /**
     * Provides data to testGuessDepth().
     */
    public function guessDepthDataProvider()
    {
        $package = new RootPackage('cweagans/composer-patches', '0.0.0.0', '0.0.0');
        $package->setExtra([]);

        $io = new NullIO();

        $composer = new Composer();
        $composer->setPackage($package);
        $composer->setEventDispatcher(new EventDispatcher($composer, $io));

        $plugin = new Patches();
        $plugin->activate($composer, $io);

        $patch = new Patch();
        $patch->package = 'some/package';
        yield 'global default depth' => [$plugin, $patch, 1];

        $patch = new Patch();
        $patch->depth = 123;
        yield 'depth set on patch' => [$plugin, $patch, 123];

        $patch = new Patch();
        $patch->package = 'drupal/core';
        yield 'depth set in global package override list' => [$plugin, $patch, 2];

        $package = new RootPackage('cweagans/composer-patches', '0.0.0.0', '0.0.0');
        $package->setExtra([
            'composer-patches' => [
                'package-depths' => [
                    'some/package' => 234,
                ],
            ],
        ]);
        $composer->setPackage($package);
        $plugin = new Patches();
        $plugin->activate($composer, $io);

        $patch = new Patch();
        $patch->package = 'some/package';
        yield 'depth set in project package override list' => [$plugin, $patch, 234];
    }
}
