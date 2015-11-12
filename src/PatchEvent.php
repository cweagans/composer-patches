<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer;

use Composer\EventDispatcher\Event;
use Composer\Package\PackageInterface;

class PatchEvent extends Event {

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
  * @var string $sha1
  */
 protected $sha1 = NULL;

  /**
   * Constructs a PatchEvent object.
   *
   * @param string $eventName
   * @param PackageInterface $package
   * @param string $url
   * @param string $description
   * @param string $sha1
   */
  public function __construct($eventName, PackageInterface $package, $url, $description, $sha1 = NULL) {
    parent::__construct($eventName);
    $this->package = $package;
    $this->url = $url;
    $this->description = $description;
    $this->sha1 = $sha1;
  }

  /**
   * Returns the package that is patched.
   *
   * @return PackageInterface
   */
  public function getPackage() {
    return $this->package;
  }

  /**
   * Returns the url of the patch.
   *
   * @return string
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Returns the description of the patch.
   *
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * Returns the sha1 check for the patch.
   *
   * @return string
   */
  public function getSha1() {
    return $this->sha1;
  }
}
