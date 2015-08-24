<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Script\PackageEvent;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Symfony\Component\Process\Process;

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
   * @var bool $useGit
   */
  protected $useGit = FALSE;

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
    $this->useGit = getenv('COMPOSER_PATCHES_USE_GIT') == '1';
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::PRE_INSTALL_CMD => "checkPatches",
      ScriptEvents::PRE_UPDATE_CMD => "checkPatches",
      PackageEvents::PRE_PACKAGE_INSTALL => "gatherPatches",
      PackageEvents::PRE_PACKAGE_UPDATE => "gatherPatches",
      PackageEvents::POST_PACKAGE_INSTALL => "postInstall",
      PackageEvents::POST_PACKAGE_UPDATE => "postInstall",
    );
  }

  /**
   * Before running composer install,
   * @param Event $event
   */
  public function checkPatches(Event $event) {
    try {
      $repositoryManager = $this->composer->getRepositoryManager();
      $localRepository = $repositoryManager->getLocalRepository();
      $installationManager = $this->composer->getInstallationManager();
      $packages = $localRepository->getPackages();

      $tmp_patches = array();

      // First, try to get the patches from the root composer.json.
      $extra = $this->composer->getPackage()->getExtra();
      if (isset($extra['patches'])) {
        $this->io->write('<info>Gathering patches for root package.</info>');
        $tmp_patches = $extra['patches'];
      }
      // If it's not specified there, look for a patches-file definition.
      else if (isset($extra['patches-file'])) {
        $this->io->write('<info>Gathering patches from patch file.</info>');
        $patches = file_get_contents($extra['patches-file']);
        $patches = json_decode($patches, TRUE);
        if (isset($patches['patches'])) {
          $tmp_patches = $patches['patches'];
        }
      }
      else {
        // @todo: should we throw an exception here?
        return;
      }

      foreach ($packages as $package) {
        $extra = $package->getExtra();
        $patches = isset($extra['patches']) ? $extra['patches'] : array();
        $tmp_patches = array_merge_recursive($tmp_patches, $patches);
      }

      // Remove packages for which the patch set has changed.
      foreach ($packages as $package) {
        if (!($package instanceof AliasPackage)) {
          $package_name = $package->getName();
          $extra = $package->getExtra();
          $has_patches = isset($tmp_patches[$package_name]);
          $has_applied_patches = isset($extra['patches_applied']);
          if (($has_patches && !$has_applied_patches)
            || (!$has_patches && $has_applied_patches)
            || ($has_patches && $has_applied_patches && $tmp_patches[$package_name] !== $extra['patches_applied'])) {
            $uninstallOperation = new UninstallOperation($package, 'Removing package so it can be re-installed and re-patched.');
            $this->io->write('<info>Removing package ' . $package_name . ' so that it can be re-installed and re-patched.</info>');
            $installationManager->uninstall($localRepository, $uninstallOperation);
          }
        }
      }
    }
      // If the Locker isn't available, then we don't need to do this.
      // It's the first time packages have been installed.
    catch (\LogicException $e) {
      return;
    }
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

    // First, try to get the patches from the root composer.json.
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches'])) {
      $this->io->write('<info>Gathering patches for root package.</info>');
      $this->patches = $extra['patches'];
    }
    // If it's not specified there, look for a patches-file definition.
    else if (isset($extra['patches-file'])) {
      $this->io->write('<info>Gathering patches from patch file.</info>');
      $patches = file_get_contents($extra['patches-file']);
      $patches = json_decode($patches, TRUE);
      if (isset($patches['patches'])) {
        $this->patches = $patches['patches'];
      }
    }
    else {
      // @todo: should we throw an exception here?
      return;
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
   * @throws \Exception
   */
  public function postInstall(PackageEvent $event) {
    // Get the package object for the current operation.
    $operation = $event->getOperation();
    /** @var PackageInterface $package */
    $package = $this->getPackageFromOperation($operation);
    $package_name = $package->getName();

    // Get the install path from the package object.
    $manager = $event->getComposer()->getInstallationManager();
    $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);

    if ($this->useGit) {
      // Commit the package.
      $this->io->write('  - Committing <info>' . $package_name . '</info> with version <info>' . $package->getVersion(). '</info> to GIT.');
      $this->executeCommand('cd %s && git add -A . && git commit -m "Update package %s to version %s"', $install_path, $package_name, $package->getVersion());
    }

    if (!isset($this->patches[$package_name])) {
      if ($this->io->isVerbose()) {
        $this->io->write('<info>No patches found for ' . $package_name . '.</info>');
      }
      return;
    }
    $this->io->write('  - Applying patches for <info>' . $package_name . '</info>');

    // Set up a downloader.
    $downloader = new RemoteFilesystem($this->io, $this->composer->getConfig());

    // Track applied patches in the package info in installed.json
    $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
    $localPackage = $localRepository->findPackage($package_name, $package->getVersion());
    $extra = $localPackage->getExtra();
    $extra['patches_applied'] = array();

    foreach ($this->patches[$package_name] as $description => $url) {
      $this->io->write('    <info>' . $url . '</info> (<comment>' . $description. '</comment>)');
      try {
        $this->getAndApplyPatch($downloader, $install_path, $url);
        if ($this->useGit) {
          $this->io->write('  - Committing patch <info>' . $url . '</info> for package <info>' . $package_name . '</info> to GIT.');
          $this->executeCommand('cd %s && git add -A . && git commit -m "Applied patch %s for %s."', $install_path, $url, $package_name);
        }
        $extra['patches_applied'][$description] = $url;
      }
      catch (\Exception $e) {
        $this->io->write('   <error>Could not apply patch! Skipping.</error>');
      }
    }
    $localPackage->setExtra($extra);

    $this->io->write('');
    $this->writePatchReport($this->patches[$package_name], $install_path);
  }

  /**
   * Get a Package object from an OperationInterface object.
   *
   * @param OperationInterface $operation
   * @return PackageInterface
   * @throws \Exception
   */
  protected function getPackageFromOperation(OperationInterface $operation) {
    if ($operation instanceof InstallOperation) {
      $package = $operation->getPackage();
    }
    elseif ($operation instanceof UpdateOperation) {
      $package = $operation->getTargetPackage();
    }
    else {
      throw new \Exception('Unknown operation: ' . get_class($operation));
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
    $output = '';
    if ($this->io->isVerbose()) {
      $this->io->write('<comment>' . $command . '</comment>');
      $io = $this->io;
      $output = function ($type, $data) use ($io) {
        if ($type == Process::ERR) {
          $io->write('<error>' . $data . '</error>');
        }
        else {
          $io->write('<comment>' . $data . '</comment>');
        }
      };
    }
    return ($this->executor->execute($command, $output) == 0);
  }
}
