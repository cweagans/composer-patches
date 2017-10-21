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
  public function testGetters($event_name, PackageInterface $package, $url, $description, $hashes) {
    $patch_event = new PatchEvent($event_name, $package, $url, $description, $hashes);
    $this->assertEquals($event_name, $patch_event->getName());
    $this->assertEquals($package, $patch_event->getPackage());
    $this->assertEquals($url, $patch_event->getUrl());
    $this->assertEquals($description, $patch_event->getDescription());
    $eventHashes = $patch_event->getHashes();
    foreach ($hashes as $algo => $hash) {
      $this->assertEquals($hash, $eventHashes[$algo]);
    }
  }

  public function patchEventDataProvider() {
    $prophecy = $this->prophesize('Composer\Package\PackageInterface');
    $package = $prophecy->reveal();

    return array(
      array(PatchEvents::PRE_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch', array()),
      array(PatchEvents::POST_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch', array()),
      array(PatchEvents::POST_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch', array(
        'sha1' => '2fd4e1c67a2d28fced849ee1bb76e7391b93eb12',
        'sha256' => 'c03905fcdab297513a620ec81ed46ca44ddb62d41cbbd83eb4a5a3592be26a69',
      )),
    );
  }

}
