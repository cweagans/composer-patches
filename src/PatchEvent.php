<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;

class PatchEvent extends Event
{
    /**
     * @var PackageInterface $package
     */
    protected PackageInterface $package;


    /**
     * @var Patch $patch
     */
    protected Patch $patch;

    /**
     * Constructs a PatchEvent object.
     *
     * @param string $eventName
     * @param PackageInterface $package
     * @param Patch $patch
     */
    public function __construct(string $eventName, PackageInterface $package, Patch $patch)
    {
        parent::__construct($eventName);
        $this->package = $package;
        $this->patch = $patch;
    }

    /**
     * Returns the package that is patched.
     *
     * @return PackageInterface
     */
    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    /**
     * Returns the Patch object.
     *
     * @return Patch
     */
    public function getPatch(): Patch
    {
        return $this->patch;
    }
}
