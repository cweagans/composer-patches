<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Plugin\Patches;
use cweagans\Composer\Resolver\PatchesFile;
use InvalidArgumentException;

class PatchesFileResolverTest extends Unit
{
    public function setUp(): void
    {
        $this->package = new RootPackage('test/package', '1.0.0.0', '1.0.0');
        $this->composer = new Composer();
        $this->composer->setPackage($this->package);
        $this->io = new NullIO();
        $this->event = Stub::make(PackageEvent::class, []);
        $this->collection = new PatchCollection();
        $plugin_stub = Stub::make(Patches::class, [
            'getConfig' => 'patches.json',
        ]);
        $this->resolver = new PatchesFile($this->composer, $this->io, $plugin_stub);
    }

    public function testHappyPath()
    {
        $plugin_stub = Stub::make(Patches::class, [
            'getConfig' => codecept_data_dir('dummyPatches.json'),
        ]);
        $this->resolver = new PatchesFile($this->composer, $this->io, $plugin_stub);

        $this->resolver->resolve($this->collection, $this->event);
        $this->assertCount(2, $this->collection->getPatchesForPackage('test/package'));
        $this->assertCount(2, $this->collection->getPatchesForPackage('test/package2'));
    }

    public function testEmptyPatches()
    {
        $plugin_stub = Stub::make(Patches::class, [
            'getConfig' => codecept_data_dir('dummyPatchesEmpty.json'),
        ]);
        $this->resolver = new PatchesFile($this->composer, $this->io, $plugin_stub);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No patches found.');

        $this->resolver->resolve($this->collection, $this->event);
    }

    public function testInvalidJSON()
    {
        $plugin_stub = Stub::make(Patches::class, [
            'getConfig' => codecept_data_dir('dummyPatchesInvalid.json'),
        ]);
        $this->resolver = new PatchesFile($this->composer, $this->io, $plugin_stub);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Syntax error');

        $this->resolver->resolve($this->collection, $this->event);
    }

    public function testPatchesFileNotFound()
    {
        $plugin_stub = Stub::make(Patches::class, [
            'getConfig' => codecept_data_dir('noSuchFile.json'),
        ]);
        $this->resolver = new PatchesFile($this->composer, $this->io, $plugin_stub);

        // Check that the collection is empty to start with
        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());

        // This error is handled silently.
        $this->resolver->resolve($this->collection, $this->event);

        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());
    }

    public function testNoPatchesFile()
    {
        // Check that the collection is empty to start with
        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());

        // This is not an error. No changes should be made to the collection.
        $this->resolver->resolve($this->collection, $this->event);

        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());
    }
}
