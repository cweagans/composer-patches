<?php

/**
 * @file
 * Test the RootComposer resolver.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolver\RootComposer;
use stdClass;

class RootComposerResolverTest extends Unit
{
    public function testResolve()
    {
        $patch_collection = new PatchCollection();
        $root_package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $root_package->setExtra(['patches' => []]);
        $composer = new Composer();
        $composer->setPackage($root_package);
        $io = new NullIO();
        $event = Stub::make(PackageEvent::class, []);
        $plugin = Stub::makeEmpty(PluginInterface::class);

        // Empty patch list.
        $resolver = new RootComposer($composer, $io, $plugin);
        $resolver->resolve($patch_collection, $event);
        $this->assertCount(0, $patch_collection->getPatchesForPackage('test/package'));

        // One patch (expanded definition)
        $root_package->setExtra([
            'patches' => [
                'test/package' => [
                    0 => [
                        'url' => 'https://drupal.org',
                        'description' => 'Test patch'
                    ],
                ],
            ],
        ]);

        $composer->setPackage($root_package);
        $resolver = new RootComposer($composer, $io, $plugin);
        $resolver->resolve($patch_collection, $event);
        $this->assertCount(1, $patch_collection->getPatchesForPackage('test/package'));


        // One patch (compact definition)
        $patch_collection = new PatchCollection();
        $root_package->setExtra([
            'patches' => [
                'test/package' => [
                    'Test patch' => 'https://drupal.org',
                ],
            ],
        ]);

        $composer->setPackage($root_package);
        $resolver = new RootComposer($composer, $io, $plugin);
        $resolver->resolve($patch_collection, $event);
        $this->assertCount(1, $patch_collection->getPatchesForPackage('test/package'));
    }
}
