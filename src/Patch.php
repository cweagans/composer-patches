<?php

namespace cweagans\Composer;

use Composer\Package\PackageInterface;
use JsonSerializable;

class Patch implements JsonSerializable
{
    /**
     * The package that the patch belongs to.
     *
     * @var string $package
     */
    public $package;

    /**
     * The description of what the patch does.
     *
     * @var string $description
     */
    public $description;

    /**
     * The URL where the patch is stored. Can be local.
     *
     * @var string $url
     */
    public $url;

    /**
     * The sha1 hash of the patch file.
     *
     * @var string $sha1
     */
    public $sha1;

    /**
     * The patch depth to use when applying the patch (-p flag for `patch`)
     *
     * @var int $depth
     */
    public $depth;

    /**
     * Create a Patch from a serialized representation.
     *
     * @param $json
     *   A json_encode'd representation of a Patch.
     *
     * @return Patch
     *   A Patch with all of the serialized properties set.
     */
    public static function fromJson($json)
    {
        if (!is_object($json)) {
            $json = json_decode($json);
        }
        $properties = ['package', 'description', 'url', 'sha1', 'depth'];
        $patch = new static();

        foreach ($properties as $property) {
            if (isset($json->{$property})) {
                $patch->{$property} = $json->{$property};
            }
        }

        return $patch;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'package' => $this->package,
            'description' => $this->description,
            'url' => $this->url,
            'sha1' => $this->sha1,
            'depth' => $this->depth,
        ];
    }

    /**
     * Indicates if a package has been patched.
     *
     * @param PackageInterface $package
     *   The package to check.
     *
     * @return bool
     *   TRUE if the package has been patched.
     */
    public static function isPackagePatched(PackageInterface $package)
    {
        return array_key_exists('patches_applied', $package->getExtra());
    }
}
