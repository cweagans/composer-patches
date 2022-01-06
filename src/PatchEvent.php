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
  * @var ?string $sha256
  */
 protected $sha256;

  /**
   * Constructs a PatchEvent object.
   *
   * @param string $eventName
   * @param PackageInterface $package
   * @param string $url
   * @param string $description
   * @param ?string $sha256
   */
  public function __construct($eventName, PackageInterface $package, $url, $description, $sha256 = NULL) {
    parent::__construct($eventName);
    $this->package = $package;
    $this->url = $url;
    $this->description = $description;
    $this->sha256 = $sha256;
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
   * Returns the sha256 checksum of the patch.
   *
   * @return ?string
   */
  public function getSha256() {
    return $this->sha256;
  }

}
