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
  * @var array $checksums
  */
 protected $checksums;

  /**
   * Constructs a PatchEvent object.
   *
   * @param string $eventName
   * @param PackageInterface $package
   * @param string $url
   * @param string $description
   * @param array $checksums
   */
  public function __construct($eventName, PackageInterface $package, $url, $description, $checksums = array()) {
    parent::__construct($eventName);
    $this->package = $package;
    $this->url = $url;
    $this->description = $description;
    $this->checksums = $checksums;
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
   * Returns the checksums of the patch.
   *
   * @return array
   */
  public function getChecksums() {
    return $this->checksums;
  }

}
