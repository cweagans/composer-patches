<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer\Event;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;
use cweagans\Composer\Patch;

class PatchEvent extends Event
{
    /**
     * @var Patch $patch
     */
    protected Patch $patch;

    /**
     * Constructs a PatchEvent object.
     *
     * @param string $eventName
     * @param Patch $patch
     */
    public function __construct(string $eventName, Patch $patch)
    {
        parent::__construct($eventName);
        $this->patch = $patch;
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
