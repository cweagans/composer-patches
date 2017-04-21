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
use Composer\EventDispatcher\EventDispatcher;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
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
   * @var EventDispatcher $eventDispatcher
   */
  protected $eventDispatcher;
  /**
   * @var ProcessExecutor $executor
   */
  protected $executor;
  /**
   * @var array $patches
   */
  protected $patches;
  /**
   * @var InstallationManager $installationManager
   */
  protected $installationManager;
  /**
   * @var WritableRepositoryInterface $localRepository
   */
  protected $localRepository;

  /**
   * Apply plugin modifications to composer
   *
   * @param Composer    $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
    $this->eventDispatcher = $composer->getEventDispatcher();
    $this->executor = new ProcessExecutor($this->io);
    $this->patches = array();
    $this->installationManager = $composer->getInstallationManager();
    $this->localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::PRE_INSTALL_CMD => "gatherAndCheckPatches",
      ScriptEvents::PRE_UPDATE_CMD => "gatherAndCheckPatches",
      ScriptEvents::POST_INSTALL_CMD => "postInstall",
      ScriptEvents::POST_UPDATE_CMD => "postInstall",
    );
  }

  /**
   * Before running composer install or update.
   *
   * @param Event $event
   */
  public function gatherAndCheckPatches(Event $event, $skipRemoval = FALSE) {
    if (!$this->isPatchingEnabled()) {
      $this->io->write('<info>Patching is disabled. Skipping.</info>', TRUE);
      return;
    }

    try {
      $packages = $this->localRepository->getPackages();
      $extra = $this->composer->getPackage()->getExtra();
      $ignore_patches = isset($extra['patches-ignore']) ? $extra['patches-ignore'] : [];
      $applied_patches = [];

      $all_patches = $this->grabPatches();

      $this->io->write('<info>Gathering patches defined by dependency packages.</info>');

      foreach ($packages as $package) {
        $extra = $package->getExtra();
        $package_name = $package->getName();

        // Gather all patches that are already applied.
        if (isset($extra['patches_applied'])) {
          $applied_patches[$package_name] = !isset($applied_patches[$package_name]) ? $extra['patches_applied'] : $this->arrayMergeRecursiveDistinct($applied_patches[$package_name], $extra['patches_applied']);
        }
        // Add all dependency patches.
        if (isset($extra['patches'])) {
          $all_patches = $this->arrayMergeRecursiveDistinct($all_patches, $extra['patches']);
        }
      }

      foreach ($ignore_patches as $package_name => $package_ignore_patches) {
        $ignored_patches = [];

        // Unset ignored patches, for both root as dependency patches.
        foreach ($package_ignore_patches as $patch) {
          if (isset($all_patches[$package_name]) && ($index = array_search($patch, $all_patches[$package_name])) !== FALSE) {
            $ignored_patches[] = $index . ' - ' . $patch;
            unset($all_patches[$package_name][$index]);
          }
        }

        // If we're in verbose mode, list the patches that are ignored.
        if ($this->io->isVerbose() && !empty($ignored_patches)) {
          $this->io->write('<info>Ignore ' . $package_name . ' patches: ' . implode(', ', $ignored_patches) . '</info>');
        }
      }

      foreach ($all_patches as $package_name => $patches) {
        if (empty($all_patches[$package_name])) {
          unset($all_patches[$package_name]);
          continue;
        }
        // If we're in verbose mode, list all found patches per package.
        if ($this->io->isVerbose()) {
          $number = count($all_patches[$package_name]);
          $this->io->write('<info>Found ' . $number . ' patches for ' . $package_name . '.</info>');
        }
      }

      $this->patches = $all_patches;

      // Remove packages for which the patch set has changed.
      if (!$skipRemoval) {
        foreach ($packages as $package) {
          if (!($package instanceof AliasPackage)) {
            $package_name = $package->getName();

            $has_patches = isset($all_patches[$package_name]);
            $has_applied_patches = isset($applied_patches[$package_name]);

            if (($has_patches && !$has_applied_patches)
              || (!$has_patches && $has_applied_patches)
              || ($has_patches && $has_applied_patches && $all_patches[$package_name] !== $applied_patches[$package_name])) {
              $this->uninstallPackage($package);
            }
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
   * Get the patches from root composer or external file.
   *
   * @return array
   *   List of patches.
   *
   * @throws \Exception
   */
  public function grabPatches() {
    // First, try to get the patches from the root composer.json.
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches'])) {
      $this->io->write('<info>Gathering patches defined by the root package.</info>');
      $patches = $extra['patches'];
      return $patches;
    }
    // If it's not specified there, look for a patches-file definition.
    elseif (isset($extra['patches-file'])) {
      $this->io->write('<info>Gathering patches from patch file.</info>');
      $patches = file_get_contents($extra['patches-file']);
      $patches = json_decode($patches, TRUE);
      $error = json_last_error();
      if ($error != 0) {
        switch ($error) {
          case JSON_ERROR_DEPTH:
            $msg = ' - Maximum stack depth exceeded';
            break;
          case JSON_ERROR_STATE_MISMATCH:
            $msg =  ' - Underflow or the modes mismatch';
            break;
          case JSON_ERROR_CTRL_CHAR:
            $msg = ' - Unexpected control character found';
            break;
          case JSON_ERROR_SYNTAX:
            $msg =  ' - Syntax error, malformed JSON';
            break;
          case JSON_ERROR_UTF8:
            $msg =  ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
          default:
            $msg =  ' - Unknown error';
            break;
        }
        throw new \Exception('There was an error in the supplied patches file:' . $msg);
      }
      if (isset($patches['patches'])) {
        $patches = $patches['patches'];
        return $patches;
      }
      elseif(!$patches) {
        throw new \Exception('There was an error in the supplied patch file');
      }
    }
    else {
      return array();
    }
  }

  /**
   * After installing / updating all packages.
   *
   * @param Event $event
   *
   * @throws \Exception
   */
  public function postInstall(Event $event) {
    if (!$this->isPatchingEnabled()) {
      return;
    }

    $packages = $this->localRepository->getPackages();

    // Get all patches that where found before installing / updating.
    $patchesBefore = $this->patches;
    $patchesPackagesBefore = array_keys($patchesBefore);

    $this->io->write('<info>Gather and check patches again in order to detect dependency patch definition changes.</info>');

    // Get all patches after installing / updating (including dependency
    // changes).
    $this->gatherAndCheckPatches($event, TRUE);

    $patchesAfter = $this->patches;
    $patchesPackagesAfter = array_keys($patchesAfter);

    $changedPackages = array();

    foreach (array_merge($patchesPackagesAfter, $patchesPackagesBefore) as $packageName) {
      // Check package additions or removals in dependency patch definitions.
      if (array_search($packageName, $patchesPackagesAfter) === FALSE || array_search($packageName, $patchesPackagesBefore) === FALSE) {
        $changedPackages[$packageName] = $packageName;
      }
      else {
        // Check patch additions or removals.
        $patchesRemoved = array_diff($patchesBefore[$packageName], $patchesAfter[$packageName]);
        $patchesAdded = array_diff($patchesAfter[$packageName], $patchesBefore[$packageName]);

        if (!empty($patchesRemoved) || !empty($patchesAdded)) {
          $changedPackages[$packageName] = $packageName;
        }
      }
    }

    if (!empty($changedPackages)) {
      $this->io->write('<info>Detected dependency package(s) patch definition changes.</info>');
      foreach ($changedPackages as $packageName) {
        $package = $this->getPackageById($packages, $packageName);
        $this->uninstallPackage($package);
        $this->installPackage($package);
      }
    }

    // Patch the packages.
    foreach ($patchesAfter as $packageName => $patches) {
      $this->io->write('  - Applying patches for <info>' . $packageName . '</info>');

      $package = $this->getPackageById($packages, $packageName);

      // Get the install path from the package object.
      $install_path = $this->installationManager->getInstaller($package->getType())->getInstallPath($package);

      // Set up a downloader.
      $downloader = new RemoteFilesystem($this->io, $this->composer->getConfig());

      // Track applied patches in the package info in installed.json.
      $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
      $localPackage = $localRepository->findPackage($packageName, $package->getVersion());
      $extra = $localPackage->getExtra();
      $extra['patches_applied'] = array();

      foreach ($patches as $description => $url) {
        $this->io->write('<info>' . $url . '</info> (<comment>' . $description . '</comment>)');
        try {
          $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $url, $description));
          $this->getAndApplyPatch($downloader, $install_path, $url);
          $this->eventDispatcher->dispatch(NULL, new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $url, $description));
          $extra['patches_applied'][$description] = $url;
        }
        catch (\Exception $e) {
          $this->io->write('   <error>Could not apply patch! Skipping. The error was: ' . $e->getMessage() . '</error>');
          $extra = $this->composer->getPackage()->getExtra();
          if (getenv('COMPOSER_EXIT_ON_PATCH_FAILURE') || !empty($extra['composer-exit-on-patch-failure'])) {
            throw new \Exception("Cannot apply patch $description ($url)!");
          }
        }
      }

      // Applied patches are only tracked in installed.json,
      // unless composer update is used.
      $localPackage->setExtra($extra);

      $this->io->write('');
      $this->writePatchReport($patches, $install_path);
    }
  }

  /**
   * Get a Package object from an OperationInterface object.
   *
   * @param PackageInterface[] $packages
   * @return PackageInterface
   * @throws \Exception
   */
  protected function getPackageById($packages, $packageName) {
    foreach ($packages as $package) {
      if ($package->getName() === $packageName) {
        return $package;
      }
    }

    throw new \Exception("Cannot find package $packageName");
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

    // Local patch file.
    if (file_exists($patch_url)) {
      $filename = realpath($patch_url);
    }
    else {
      // Generate random (but not cryptographically so) filename.
      $filename = uniqid(sys_get_temp_dir().'/') . ".patch";

      // Download file from remote filesystem to this location.
      $hostname = parse_url($patch_url, PHP_URL_HOST);
      $downloader->copy($hostname, $patch_url, $filename, FALSE);
    }

    // Modified from drush6:make.project.inc
    $patched = FALSE;
    // The order here is intentional. p1 is most likely to apply with git apply.
    // p0 is next likely. p2 is extremely unlikely, but for some special cases,
    // it might be useful.
    $patch_levels = array('-p1', '-p0', '-p2');
    foreach ($patch_levels as $patch_level) {
      $checked = $this->executeCommand('cd %s && git --git-dir=. apply --check %s %s', $install_path, $patch_level, $filename);
      if ($checked) {
        // Apply the first successful style.
        $patched = $this->executeCommand('cd %s && git --git-dir=. apply %s %s', $install_path, $patch_level, $filename);
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

    // Clean up the temporary patch file.
    if (isset($hostname)) {
      unlink($filename);
    }
    // If the patch *still* isn't applied, then give up and throw an Exception.
    // Otherwise, let the user know it worked.
    if (!$patched) {
      throw new \Exception("Cannot apply patch $patch_url");
    }
  }

  /**
   * Checks if the root package enables patching.
   *
   * @return bool
   *   Whether patching is enabled. Defaults to TRUE.
   */
  protected function isPatchingEnabled() {
    $extra = $this->composer->getPackage()->getExtra();

    if (empty($extra['patches']) && empty($extra['patches-ignore']) && !isset($extra['patches-file'])) {
      // The root package has no patches of its own, so only allow patching if
      // it has specifically opted in.
      return isset($extra['enable-patching']) ? $extra['enable-patching'] : FALSE;
    }
    else {
      return TRUE;
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

  /**
   * Recursively merge arrays without changing data types of values.
   *
   * Does not change the data types of the values in the arrays. Matching keys'
   * values in the second array overwrite those in the first array, as is the
   * case with array_merge.
   *
   * @param array $array1
   *   The first array.
   * @param array $array2
   *   The second array.
   * @return array
   *   The merged array.
   *
   * @see http://php.net/manual/en/function.array-merge-recursive.php#92195
   */
  protected function arrayMergeRecursiveDistinct(array $array1, array $array2) {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = $this->arrayMergeRecursiveDistinct($merged[$key], $value);
      }
      else {
        $merged[$key] = $value;
      }
    }

    return $merged;
  }

  /**
   * Uninstall a package so that it can be re-patched.
   *
   * @param PackageInterface $package
   */
  protected function uninstallPackage($package): void {
    $uninstallOperation = new UninstallOperation($package, 'Removing package so it can be re-installed and re-patched when necessary.');
    $this->io->write('<info>Removing package ' . $package->getName() . ' so that it can be re-installed and re-patched when necessary.</info>');
    $this->installationManager->uninstall($this->localRepository, $uninstallOperation);
  }

  /**
   * Install a package.
   *
   * @param PackageInterface $package
   */
  protected function installPackage($package): void {
    $installOperation = new InstallOperation($package, 'Installing package.');
    $this->installationManager->install($this->localRepository, $installOperation);
  }

}
