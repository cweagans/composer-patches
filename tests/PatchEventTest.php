<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests;

use cweagans\Composer\PatchEvent;
use cweagans\Composer\PatchEvents;
use Composer\Package\PackageInterface;

class PatchEventTest extends \PHPUnit_Framework_TestCase {

  /**
   * Tests all the getters.
   *
   * @dataProvider patchEventDataProvider
   */
  public function testGetters($event_name, PackageInterface $package, $url, $description, $sha1) {
    $patch_event = new PatchEvent($event_name, $package, $url, $description);
    $this->assertEquals($event_name, $patch_event->getName());
    $this->assertEquals($package, $patch_event->getPackage());
    $this->assertEquals($url, $patch_event->getUrl());
    $this->assertEquals($description, $patch_event->getDescription());
    $this->assertEquals($sha1, $patch_event->getSha1());
  }

  public function patchEventDataProvider() {
    $prophecy = $this->prophesize('Composer\Package\PackageInterface');
    $package = $prophecy->reveal();

    return array(
      array(PatchEvents::PRE_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch', NULL),
      array(PatchEvents::POST_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch', NULL),
    );
  }

}
