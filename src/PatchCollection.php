<?php

namespace cweagans\Composer;

class PatchCollection
{
    /**
     * A deep list of patches to apply.
     *
     * Keys are package names. Values are arrays of Patch objects.
     *
     * @var array
     */
    protected $patches;

    /**
     * Add a patch to the collection.
     *
     * @param Patch $patch
     *   The patch object to add to the collection.
     */
    public function addPatch(Patch $patch)
    {
        $this->patches[$patch->package][] = $patch;
    }

    /**
     * Retrieve a list of patches for a given package.
     *
     * @param string $package
     *   The package name to get patches for.
     *
     * @return array
     *   An array of Patch objects.
     */
    public function getPatchesForPackage($package)
    {
        if (isset($this->patches[$package])) {
            return $this->patches[$package];
        }

        return [];
    }
}
