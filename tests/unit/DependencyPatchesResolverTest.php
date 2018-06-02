<?php

/**
 * @file
 * Test the RootComposer resolver.
 */
namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolvers\DependencyPatches;
use cweagans\Composer\Resolvers\RootComposer;

class DependencyPatchesResolverTest extends Unit
{
    public function testResolve()
    {
        $patch_collection = new PatchCollection();
        $root_package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $root_package->setExtra(['patches' => []]);
        $composer = new Composer();
        $composer->setPackage($root_package);
        $io = new NullIO();
        $event = Stub::make(PackageEvent::class, [
            'getOperations' => function () {
                return [];
            },
        ]);

        // Empty patch list.
        $resolver = new DependencyPatches($composer, $io);
        $resolver->resolve($patch_collection, $event);
        $this->assertCount(0, $patch_collection->getPatchesForPackage('test/package'));

        // @TODO: Add operations to the event and test that the resolver finds patches appropriately.
    }
}
