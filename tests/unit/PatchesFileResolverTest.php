<?php

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolvers\PatchesFile;

class PatchesFileResolverTest extends Unit
{
    public function testHappyPath()
    {
        $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $package->setExtra([
            'patches-file' => __DIR__ . '/../_data/dummyPatches.json',
        ]);

        $composer = new Composer();
        $composer->setPackage($package);
        $io = new NullIO();
        $event = Stub::make(PackageEvent::class, []);

        $collection = new PatchCollection();
        $resolver = new PatchesFile($composer, $io);
        $resolver->resolve($collection, $event);
        $this->assertCount(2, $collection->getPatchesForPackage('test/package'));
        $this->assertCount(2, $collection->getPatchesForPackage('test/package2'));
    }

    public function testEmptyPatches()
    {
        try {
            $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
            $package->setExtra([
                'patches-file' => __DIR__ . '/../_data/dummyPatchesEmpty.json',
            ]);

            $composer = new Composer();
            $composer->setPackage($package);
            $io = new NullIO();
            $event = Stub::make(PackageEvent::class, []);

            $collection = new PatchCollection();
            $resolver = new PatchesFile($composer, $io);
            $resolver->resolve($collection, $event);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No patches found.', $e->getMessage());
        }
    }

    public function testInvalidJSON()
    {
        try {
            $package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
            $package->setExtra([
                'patches-file' => __DIR__ . '/../_data/dummyPatchesInvalid.json',
            ]);

            $composer = new Composer();
            $composer->setPackage($package);
            $io = new NullIO();
            $event = Stub::make(PackageEvent::class, []);

            $collection = new PatchCollection();
            $resolver = new PatchesFile($composer, $io);
            $resolver->resolve($collection, $event);
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Syntax error', $e->getMessage());
        }
    }
}
