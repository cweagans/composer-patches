<?php

namespace cweagans\Composer;

class PatchOptionsCollection implements \JsonSerializable
{
    /**
     * A deep list of patches to apply.
     *
     * Keys are package names. Values are arrays of Patch objects.
     *
     * @var array
     */
    protected $options;

    /**
     * Add a patch to the collection.
     *
     * @param PatchOptions $option
     *   The patch object to add to the collection.
     */
    public function addOptions(PatchOptions $option)
    {
        $this->options[$option->package][$option->url] = $option;
    }

    /**
     * Retrieve a list of options for a given package.
     *
     * @param string $package
     *   The package name to get options for.
     *
     * @return PatchOptions|null
     *   A PatchOptions or null if none are found
     */
    public function getOptionsForPatch($package, $url)
    {
        if (isset($this->options[$package][$url])) {
            return $this->options[$package][$url];
        }

        return;
    }

    /**
     * Create a PatchCollection from a serialized representation.
     *
     * @param $json
     *   A json_encode'd representation of a PatchCollection.
     *
     * @return PatchOptionsCollection
     *   A PatchOptionsCollection with all of the serialized options included.
     */
    public static function fromJson($json)
    {
        if (!is_object($json)) {
            $json = json_decode($json);
        }
        $collection = new static();

        foreach ($json->options as $package => $options) {
            foreach ($options as $patch_json) {
                $patch = Patch::fromJson($patch_json);
                $collection->addPatchOptions($patch);
            }
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'patches-options' => $this->options,
        ];
    }
}
