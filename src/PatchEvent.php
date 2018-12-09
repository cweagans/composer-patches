<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;

class PatchEvent extends Event
{

    /**
    * @var Composer $composer
    */
    protected $composer;
    /**
    * @var IOInterface $package
    */
    protected $io;
    /**
    * @var PackageInterface $package
    */
    protected $package;
    /**
     * @var string $url
     */
    protected $url;
    /**
     * @var string $description
     */
    protected $description;
    /**
     * @var \Exception $error
     */
    protected $error;

    /**
     * Constructs a PatchEvent object.
     *
     * @param string $eventName
     * @param Composer $composer
     * @param PackageInterface $package
     * @param string $url
     * @param string $description
     * @param \Exception $error
     */
    public function __construct($eventName, Composer $composer, IOInterface $io, PackageInterface $package, $url, $description, \Exception $error = null)
    {
        parent::__construct($eventName);
        $this->composer = $composer;
        $this->package = $package;
        $this->url = $url;
        $this->description = $description;
        $this->error = $error;
        $this->io - $io;
    }

    /**
     * Returns the composer object.
     *
     * @return Composer
     */
    public function getComposer()
    {
        return $this->composer;
    }

    /**
     * Returns the IOInterface.
     *
     * @return IOInterface
     */
    public function getIO()
    {
        return $this->io;
    }

    /**
     * Returns the package that is patched.
     *
     * @return PackageInterface
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Returns the url of the patch.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the description of the patch.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns the error occurred while applying patch if any.
     *
     * @return \Exception
     */
    public function getError()
    {
        return $this->error;
    }
}
