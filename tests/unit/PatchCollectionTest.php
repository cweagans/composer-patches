<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Patch;

class PatchCollectionTest extends Unit
{
    /**
     * Tests adding and retrieving patches.
     */
    public function testPatchCollectionAddRetrieve()
    {
        $collection = new PatchCollection();

        // First, make sure that we don't get anything out of an empty collection.
        $this->assertEmpty($collection->getPatchesForPackage('some/package'));

        // Next, add a couple of patches for different packages.
        $patch1 = new Patch();
        $patch1->package = 'some/package';
        $patch1->description = 'patch1';
        $patch1->url = '1';
        $collection->addPatch($patch1);

        $patch2 = new Patch();
        $patch2->package = 'some/package';
        $patch2->description = 'patch2';
        $patch2->url = '2';
        $collection->addPatch($patch2);

        $patch3 = new Patch();
        $patch3->package = 'other/package';
        $patch3->description = 'patch3';
        $patch3->url = '3';
        $collection->addPatch($patch3);

        $patch4 = new Patch();
        $patch4->package = 'other/package';
        $patch4->description = 'patch4';
        $patch4->url = '4';
        $collection->addPatch($patch4);

        foreach (['some/package', 'other/package'] as $package_name) {
            // We should get 2 patches each for some/package and other/package.
            $this->assertCount(2, $collection->getPatchesForPackage($package_name));

            // The patches returned should match the requested package name.
            foreach ($collection->getPatchesForPackage($package_name) as $patch) {
                /** @var Patch $patch */
                $this->assertEquals($package_name, $patch->package);
            }
        }

        $packages = $collection->getPatchedPackages();
        $this->assertCount(2, $packages);
        $this->assertContains('other/package', $packages);
        $this->assertContains('some/package', $packages);

        $collection->clearPatchesForPackage('other/package');
        $this->assertCount(0, $collection->getPatchesForPackage('other/package'));

        $packages = $collection->getPatchedPackages();
        $this->assertCount(1, $packages);
        $this->assertNotContains('other/package', $packages);
        $this->assertContains('some/package', $packages);
    }

    public function testPatchDeduplication()
    {
        $collection = new PatchCollection();

        // First, make sure that we don't get anything out of an empty collection.
        $this->assertEmpty($collection->getPatchesForPackage('some/package'));

        // Add two patches with the same URL.
        $patch1 = new Patch();
        $patch1->package = 'some/package';
        $patch1->description = 'patch1';
        $patch1->url = 'https://example.com';
        $collection->addPatch($patch1);

        $patch2 = new Patch();
        $patch2->package = 'some/package';
        $patch2->description = 'patch2';
        $patch2->url = 'https://example.com';
        $collection->addPatch($patch2);

        // We should only have one patch now.
        $this->assertCount(1, $collection->getPatchedPackages('some/package'));
        $this->assertEquals('patch1', $collection->getPatchesForPackage('some/package')[0]->description);

        // Start over to test deduplication with sha256.
        $collection = new PatchCollection();
        $this->assertEmpty($collection->getPatchesForPackage('some/package'));

        $patch1->sha256 = 'asdf';
        $patch2->sha256 = 'asdf';
        $patch2->url = 'https://example.com#something-different';

        $collection->addPatch($patch1);
        $collection->addPatch($patch2);

        $this->assertCount(1, $collection->getPatchedPackages('some/package'));
        $this->assertEquals('patch1', $collection->getPatchesForPackage('some/package')[0]->description);
    }

    public function testSerializeDeserialize()
    {
        $collection = new PatchCollection();

        $patch1 = new Patch();
        $patch1->package = 'some/package';
        $patch1->description = 'patch1';
        $patch1->url = 'https://example.com/test.patch';
        $patch1->extra = [];

        $patch2 = new Patch();
        $patch2->package = 'another/package';
        $patch2->description = 'patch2';
        $patch2->url = 'https://example.com/test2.patch';
        $patch2->extra = [];

        $collection->addPatch($patch1);
        $collection->addPatch($patch2);

        $json = json_encode($collection);

        $new_collection = PatchCollection::fromJson($json);

        $this->assertEquals($collection, $new_collection);

        foreach (['some/package', 'another/package'] as $package_name) {
            // We should get 1 patch for each package.
            $this->assertCount(1, $new_collection->getPatchesForPackage($package_name));

            foreach ($new_collection->getPatchesForPackage($package_name) as $patch) {
                $this->assertEquals($package_name, $patch->package);
            }
        }
    }
}
