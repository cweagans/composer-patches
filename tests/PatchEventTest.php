<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests;

use Composer\Util\Filesystem;
use cweagans\Composer\PatchEvent;
use cweagans\Composer\PatchEvents;
use Composer\Package\PackageInterface;
use PHPUnit\Framework\TestCase;

class PatchEventTest extends TestCase {

  /**
   * @var \Composer\Util\Filesystem
   */
  protected $fs;

  /**
   * @var string
   */
  protected $tmpDir;

  /**
   * @var string
   */
  protected $rootDir;

  /**
   * SetUp test
   */
  public function setUp() {
    $this->rootDir = realpath(realpath(__DIR__ . '/..'));

    // Prepare temp directory.
    $this->fs = new Filesystem();
    $this->tmpDir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'patches';
    $this->ensureDirectoryExistsAndClear($this->tmpDir);

    $this->writeComposerJSON();
    $this->copyTestPatch();

    chdir($this->tmpDir);
  }

  /**
   * tearDown
   *
   * @return void
   */
  public function tearDown()
  {
    $this->fs->removeDirectory($this->tmpDir);
  }

  /**
   * Tests a composer install and update to ensure Vagrantfile is added.
   */
  public function testComposerInstallWithPatch() {
    $patchFile = $this->tmpDir . '/vendor/composer/semver/composer-patches.txt';
    $this->assertFileNotExists($patchFile, 'patch file should not exist.');
    $this->composer('install');
    $this->assertFileExists($patchFile, 'patch file should exist after install.');
    $this->fs->removeDirectory($this->tmpDir . '/vendor/composer/semver');
    $this->composer('config extra.patches.composer/semver --unset');
    $this->composer('install');
    $this->assertFileNotExists($patchFile, 'patch file should not exist.');
  }

  /**
   * Writes the default composer json to the temp direcoty.
   */
  protected function writeComposerJSON() {
    $json = json_encode($this->composerJSONDefaults(), JSON_PRETTY_PRINT);
    file_put_contents($this->tmpDir . '/composer.json', $json);
  }

  /**
   * Writes the test patch to the temp direcoty.
   */
  protected function copyTestPatch() {
    copy($this->rootDir . '/tests/test.patch', $this->tmpDir . '/test.patch');
  }

  /**
   * Provides the default composer.json data.
   *
   * @return array
   */
  protected function composerJSONDefaults() {
    return array(
      'repositories' => array(
        array(
          'type' => 'path',
          'url' => $this->rootDir,
        )
      ),
      'minimum-stability' => 'dev',
      'require' => array(
        'composer/semver' => "*",
        'cweagans/composer-patches' => "*"
      ),
      'extra' => array(
        'patches' => array(
          'composer/semver' => array(
            'test-patch' => 'test.patch'
          )
        )
      )
    );
  }

  /**
   * Wrapper for the composer command.
   *
   * @param string $command
   *   Composer command name, arguments and/or options
   *
   * @throws \Exception
   */
  protected function composer($command) {
    chdir($this->tmpDir);
    passthru(escapeshellcmd($this->rootDir . '/vendor/bin/composer ' . $command), $exit_code);
    if ($exit_code !== 0) {
      throw new \Exception('Composer returned a non-zero exit code');
    }
  }

  /**
   * Makes sure the given directory exists and has no content.
   *
   * @param string $directory
   */
  protected function ensureDirectoryExistsAndClear($directory) {
    if (is_dir($directory)) {
      $this->fs->removeDirectory($directory);
    }
    mkdir($directory, 0777, true);
  }

  /**
   * Tests all the getters.
   *
   * @dataProvider patchEventDataProvider
   */
  public function testGetters($event_name, PackageInterface $package, $url, $description) {
    $patch_event = new PatchEvent($event_name, $package, $url, $description);
    $this->assertEquals($event_name, $patch_event->getName());
    $this->assertEquals($package, $patch_event->getPackage());
    $this->assertEquals($url, $patch_event->getUrl());
    $this->assertEquals($description, $patch_event->getDescription());
  }

  public function patchEventDataProvider() {
    $prophecy = $this->prophesize('Composer\Package\PackageInterface');
    $package = $prophecy->reveal();

    return array(
      array(PatchEvents::PRE_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch'),
      array(PatchEvents::POST_PATCH_APPLY, $package, 'https://www.drupal.org', 'A test patch'),
    );
  }

}
