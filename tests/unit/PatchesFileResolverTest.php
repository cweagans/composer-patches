<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackage;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolvers\PatchesFile;
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
        $this->resolver = new PatchesFile($this->composer, $this->io);
    }

    public function testHappyPath()
    {
        $this->package->setExtra([
            'patches-file' => __DIR__ . '/../_data/dummyPatches.json',
        ]);

        $this->resolver->resolve($this->collection, $this->event);
        $this->assertCount(2, $this->collection->getPatchesForPackage('test/package'));
        $this->assertCount(2, $this->collection->getPatchesForPackage('test/package2'));
    }

    public function testEmptyPatches()
    {
        $this->package->setExtra([
            'patches-file' => __DIR__ . '/../_data/dummyPatchesEmpty.json',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No patches found.');

        $this->resolver->resolve($this->collection, $this->event);
    }

    public function testInvalidJSON()
    {
        $this->package->setExtra([
            'patches-file' => __DIR__ . '/../_data/dummyPatchesInvalid.json',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Syntax error');

        $this->resolver->resolve($this->collection, $this->event);
    }

    public function testPatchesFileNotFound()
    {
        $this->package->setExtra([
            'patches-file' => __DIR__ . '/../_data/noSuchFile.json',
        ]);

        // Check that the collection is emtpy to start with
        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());

        // This error is handled silently.
        $this->resolver->resolve($this->collection, $this->event);

        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());
    }

    public function testNoPatchesFile()
    {
        // Check that the collection is emtpy to start with
        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());

        // This is not an error. No changes should be made to the collection.
        $this->resolver->resolve($this->collection, $this->event);

        $this->assertSame(['patches' => []], $this->collection->jsonSerialize());
    }
}
