<?php

namespace cweagans\Composer;

use JsonSerializable;

class PatchCollection implements JsonSerializable
{
    /**
     * A deep list of patches to apply.
     *
     * Keys are package names. Values are arrays of Patch objects.
     *
     * @var array
     */
    protected array $patches = [];

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
    public function getPatchesForPackage(string $package): array
    {
        if (isset($this->patches[$package])) {
            return $this->patches[$package];
        }

        return [];
    }

    /**
     * Delete all patches for a package.
     *
     * @param string $package
     *   The package name to clear patches from.
     */
    public function clearPatchesForPackage(string $package): void
    {
        if (isset($this->patches[$package])) {
            unset($this->patches[$package]);
        }
    }

    /**
     * Create a PatchCollection from a serialized representation.
     *
     * @param $json
     *   A JSON representation of a PatchCollection (or an array from JsonFile).
     *
     * @return PatchCollection
     *   A PatchCollection with all of the serialized patches included.
     */
    public static function fromJson($json): static
    {
        if (!is_array($json)) {
            $json = json_decode($json, true);
        }
        $collection = new static();

        foreach ($json['patches'] as $package => $patches) {
            foreach ($patches as $patch_json) {
                $patch = Patch::fromJson($patch_json);
                $collection->addPatch($patch);
            }
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'patches' => $this->patches,
        ];
    }
}
