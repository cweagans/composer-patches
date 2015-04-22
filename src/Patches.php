<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;

class Patches implements PluginInterface, EventSubscriberInterface {

  /**
   * @var Composer $composer
   */
  protected $composer;
  /**
   * @var IOInterface $io
   */
  protected $io;
  /**
   * @var ProcessExecutor $executor
   */
  protected $executor;

  /**
   * Apply plugin modifications to composer
   *
   * @param Composer    $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->executor = new ProcessExecutor($this->io);
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return [
      PackageEvents::POST_PACKAGE_INSTALL => "postInstall",
      PackageEvents::POST_PACKAGE_UPDATE => "postInstall"
    ];
  }

  /**
   * This is a ridiculous hack. Composer wasn't calling my method with the
   * event subscriber for some reason, so adding a static wrapper around
   * activate and postInstall() seemed like the least annoying thing to do.
   *
   * @param PackageEvent $event
   * @throws Exception
   */
  public static function postInstallStatic(PackageEvent $event) {
    $obj = new Patches();
    $composer = $event->getComposer();
    $io = $event->getIO();
    $obj->activate($composer, $io);
    $obj->postInstall($event);
  }

  /**
   * @param PackageEvent $event
   * @throws Exception
   */
  public function postInstall(PackageEvent $event) {
    // Get the package object for the current operation.
    $operation = $event->getOperation();
    if ($operation instanceof InstallOperation) {
      $package = $event->getOperation()->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $event->getOperation()->getTargetPackage();
    }
    else {
      throw new Exception('Unknown operation: ' . get_class($operation));
    }

    /**
     * @var PackageInterface $package
     */
    $extra = $this->composer->getPackage()->getExtra();
    $package_name = $package->getName();
    if (!isset($extra['patches']) || !isset($extra['patches'][$package_name])) {
      if ($this->io->isVerbose()) {
        $this->io->write('<info>No patches found for ' . $package_name . '.</info>');
      }
      return;
    }

    // Get the install path from the package object.
    $manager = $event->getComposer()->getInstallationManager();
    $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);

    // Set up a downloader.
    $downloader = new RemoteFilesystem($this->io, $this->composer->getConfig());

    foreach ($extra['patches'][$package_name] as $description => $url) {
      $message = '<comment>Applying patch: ' . $description . ' (fetching from ' . $url . ')</comment>';
      $this->io->write($message);
      try {
        $this->getAndApplyPatch($downloader, $install_path, $url);
      }
      catch (Exception $e) {
        $this->io->write('<error>Could not apply patch! Skipping.</error>');
      }
    }

    $this->writePatchReport($extra['patches'][$package_name], $install_path);
  }

  /**
   * Apply a patch on code in the specified directory.
   *
   * @param RemoteFilesystem $downloader
   * @param $install_path
   * @param $patch_url
   * @throws \Exception
   */
  protected function getAndApplyPatch(RemoteFilesystem $downloader, $install_path, $patch_url) {
    // Generate random (but not cryptographically so) filename.
    $filename = uniqid("/tmp/") . ".patch";

    // Download file from remote filesystem to this location.
    $hostname = parse_url($patch_url, PHP_URL_HOST);
    $downloader->copy($hostname, $patch_url, $filename, FALSE);

    // Modified from drush6:make.project.inc
    $patched = FALSE;
    $patch_levels = array('-p1', '-p0');
    foreach ($patch_levels as $patch_level) {
      $checked = $this->executeCommand('cd %s && GIT_DIR=. git apply --check %s %s', $install_path, $patch_level, $filename);
      if ($checked) {
        // Apply the first successful style.
        $patched = $this->executeCommand('cd %s && GIT_DIR=. git apply %s %s', $install_path, $patch_level, $filename);
        break;
      }
    }

    // In some rare cases, git will fail to apply a patch, fallback to using
    // the 'patch' command.
    if (!$patched) {
      foreach ($patch_levels as $patch_level) {
        // --no-backup-if-mismatch here is a hack that fixes some
        // differences between how patch works on windows and unix.
        if ($patched = $this->executeCommand("patch %s --no-backup-if-mismatch -d %s < %s", $patch_level, $install_path, $filename)) {
          break;
        }
      }
    }

    // Clean up the old patch file.
    unlink($filename);

    // If the patch *still* isn't applied, then give up and throw an Exception.
    // Otherwise, let the user know it worked.
    if (!$patched) {
      throw new \Exception("Cannot apply patch $patch_url");
    }
    else {
      $this->io->write("<info>Success!</info>");
    }
  }

  /**
   * Writes a patch report to the target directory.
   *
   * @param array $patches
   * @param string $directory
   */
  protected function writePatchReport($patches, $directory) {
    $output = "This file was automatically generated by Composer Patches (https://github.com/cweagans/composer-patches)\n";
    $output .= "Patches applied to this directory:\n\n";
    foreach ($patches as $description => $url) {
      $output .= $description . "\n";
      $output .= 'Source: ' . $url . "\n\n\n";
    }
    file_put_contents($directory . "/PATCHES.txt", $output);
  }

  /**
   * Executes a shell command with escaping.
   *
   * @param string $cmd
   * @return bool
   */
  protected function executeCommand($cmd) {
    // Shell-escape all arguments except the command.
    $args = func_get_args();
    foreach ($args as $index => $arg) {
      if ($index !== 0) {
        $args[$index] = escapeshellarg($arg);
      }
    }

    // And replace the arguments.
    $command = call_user_func_array('sprintf', $args);
    return ($this->executor->execute($command) == 0);
  }
}
