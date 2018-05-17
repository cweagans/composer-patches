<?php

namespace cweagans\Composer;

class Patch
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
}
