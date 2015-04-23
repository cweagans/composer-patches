<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
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
   * @var array $patches
   */
  protected $patches;

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
    $this->patches = array();
//    $lock_data = $this->composer->getLocker()->getLockData();
//    $all_packages = isset($lock_data['packages']) ? $lock_data['packages'] : array();
//    $all_packages = array();

//    foreach ($all_packages as $package) {
//      $patches = (isset($package['extra']) && isset($package['extra']['patches'])) ? $package['extra']['patches'] : array();
//      $this->patches = array_merge_recursive($this->patches, $patches);
//    }

//    var_dump($this->patches);
//    die();
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return [
      PackageEvents::PRE_PACKAGE_INSTALL => "gatherPatches",
      PackageEvents::PRE_PACKAGE_UPDATE => "gatherPatches",
      PackageEvents::POST_PACKAGE_INSTALL => "postInstall",
      PackageEvents::POST_PACKAGE_UPDATE => "postInstall",
    ];
  }

  /**
   * Gather patches from dependencies and store them for later use.
   *
   * @param PackageEvent $event
   */
  public function gatherPatches(PackageEvent $event) {
    // If we've already done this, then don't do it again.
    if (isset($this->patches['_patchesGathered'])) {
      return;
    }

    // Get patches from the root package first.
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches'])) {
      $this->io->write('<info>Gathering patches for root package.</info>');
      $this->patches = $extra['patches'];
    }

    // Now add all the patches from dependencies that will be installed.
    $operations = $event->getOperations();
    $this->io->write('<info>Gathering patches for dependencies. This might take a minute.</info>');
    foreach ($operations as $operation) {
      if ($operation->getJobType() == 'install' || $operation->getJobType() == 'update') {
        $package = $this->getPackageFromOperation($operation);
        $extra = $package->getExtra();
        if (isset($extra['patches'])) {
          $this->patches = array_merge_recursive($this->patches, $extra['patches']);
        }
      }
    }

    // If we're in verbose mode, list the projects we're going to patch.
    if ($this->io->isVerbose()) {
      foreach ($this->patches as $package => $patches) {
        $number = count($patches);
        $this->io->write('<info>Found ' . $number . ' patches for ' . $package . '.</info>');
      }
    }

    // Make sure we don't gather patches again. Extra keys in $this->patches
    // won't hurt anything, so we'll just stash it there.
    $this->patches['_patchesGathered'] = TRUE;
  }

  /**
   * @param PackageEvent $event
   * @throws Exception
   */
  public function postInstall(PackageEvent $event) {
    // Get the package object for the current operation.
    $operation = $event->getOperation();
    $package = $this->getPackageFromOperation($operation);

    /**
     * @var PackageInterface $package
     */
    $package_name = $package->getName();
    if (!isset($this->patches[$package_name])) {
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

    foreach ($this->patches[$package_name] as $description => $url) {
      $message = '<comment>Applying patch: ' . $description . ' (fetching from ' . $url . ')</comment>';
      $this->io->write($message);
      try {
        $this->getAndApplyPatch($downloader, $install_path, $url);
      }
      catch (Exception $e) {
        $this->io->write('<error>Could not apply patch! Skipping.</error>');
      }
    }

    $this->writePatchReport($this->patches[$package_name], $install_path);
  }

  /**
   * Get a Package object from an OperationInterface object.
   *
   * @param OperationInterface $operation
   * @return PackageInterface
   * @throws Exception
   */
  protected function getPackageFromOperation(OperationInterface $operation) {
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    else {
      throw new Exception('Unknown operation: ' . get_class($operation));
    }

    return $package;
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
    // The order here is intentional. p1 is most likely to apply with git apply.
    // p0 is next likely. p2 is extremely unlikely, but for some special cases,
    // it might be useful.
    $patch_levels = array('-p1', '-p0', '-p2');
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
