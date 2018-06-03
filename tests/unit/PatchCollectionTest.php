<?php

namespace cweagans\Composer\Tests;

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
        $collection->addPatch($patch1);

        $patch2 = new Patch();
        $patch2->package = 'some/package';
        $patch2->description = 'patch2';
        $collection->addPatch($patch2);

        $patch3 = new Patch();
        $patch3->package = 'other/package';
        $patch3->description = 'patch3';
        $collection->addPatch($patch3);

        $patch4 = new Patch();
        $patch4->package = 'other/package';
        $patch4->description = 'patch4';
        $collection->addPatch($patch4);

        foreach ([ 'some/package', 'other/package' ] as $package_name) {
            // We should get 2 patches each for some/package and other/package.
            $this->assertCount(2, $collection->getPatchesForPackage($package_name));

            // The patches returned should match the requested package name.
            foreach ($collection->getPatchesForPackage($package_name) as $patch) {
                /** @var Patch $patch */
                $this->assertEquals($package_name, $patch->package);
            }
        }
    }

    public function testSerializeDeserialize()
    {
        $collection = new PatchCollection();

        $patch1 = new Patch();
        $patch1->package = 'some/package';
        $patch1->description = 'patch1';

        $patch2 = new Patch();
        $patch2->package = 'another/package';
        $patch2->description = 'patch2';

        $collection->addPatch($patch1);
        $collection->addPatch($patch2);

        $json = json_encode($collection);

        $new_collection = PatchCollection::fromJson($json);

        $this->assertEquals($collection, $new_collection);

        foreach ([ 'some/package', 'another/package'] as $package_name) {
            // We should get 1 patch for each package.
            $this->assertCount(1, $new_collection->getPatchesForPackage($package_name));

            foreach ($new_collection->getPatchesForPackage($package_name) as $patch) {
                $this->assertEquals($package_name, $patch->package);
            }
        }
    }
}
