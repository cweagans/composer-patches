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
     * @var string $url
     */
    protected string $url;

    /**
     * @var string $description
     */
    protected string $description;

    /**
     * Constructs a PatchEvent object.
     *
     * @param string $eventName
     * @param PackageInterface $package
     * @param string $url
     * @param string $description
     */
    public function __construct(string $eventName, PackageInterface $package, string $url, string $description)
    {
        parent::__construct($eventName);
        $this->package = $package;
        $this->url = $url;
        $this->description = $description;
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
     * Returns the url of the patch.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Returns the description of the patch.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
