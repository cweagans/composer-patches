<?php

namespace cweagans\Composer;

class PatchOptions implements \JsonSerializable
{
    /**
     * The package that the patch belongs to.
     *
     * @var string $package
     */
    public $package;

    /**
     * The URL where the patch is stored. Can be local.
     *
     * @var string $url
     */
    public $url;

    /**
     * Should we do binary mode?
     *
     * @var boolean $binary
     */
    public $binary;

    /**
     * Create a Patch from a serialized representation.
     *
     * @param $json
     *   A json_encode'd representation of a Patch.
     *
     * @return PatchOptions
     *   A Patch with all of the serialized properties set.
     */
    public static function fromJson($json)
    {
        if (!is_object($json)) {
            $json = json_decode($json);
        }
        $properties = ['package', 'url', 'binary'];
        $patchOptions = new static();

        foreach ($properties as $property) {
            if (isset($json->{$property})) {
                $patchOptions->{$property} = $json->{$property};
            }
        }

        return $patchOptions;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'package' => $this->package,
            'url' => $this->url,
            'binary' => $this->binary
        ];
    }
}
