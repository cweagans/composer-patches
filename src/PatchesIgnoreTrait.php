<?php

/**
 * @file
 * For Patches Ignore functionality to piggy-back onto the main class.
 */

namespace cweagans\Composer;

use Composer\Package\PackageInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Trait to get the patches ignore functionality hooked into the main class.
 *
 * Using this trait will enhance the functionality of the Patches class.
 *
 */
trait PatchesIgnoreTrait {


  protected $full_patch_ignore_list;
  protected $full_patch_list;

  public function doPatchesIgnoreCollation() {
    // Second Pass: Remove patches from patches-ignore in dependencies
    if ($this->isPackagePatchingEnabled()) {
      $full_patches_ignore = $this->full_patch_ignore_list;
      $installedPatches = $this->installedPatches;
      foreach ($this->packages as $package) {
        $extra = $package->getExtra();
        // Apply the package patches-ignore list
        if ($this->checkPatchesIgnoreLegal($package) && isset($extra['patches-ignore'])) {
          $package_patches_ignore = isset($extra['patches-ignore']) ? $extra['patches-ignore'] : array();
          $full_patches_ignore = $this->arrayMergeRecursiveDistinct($full_patches_ignore, $package_patches_ignore);
          foreach ($this->flattenPatchesIgnore($package_patches_ignore) as $package_name => $patches_to_ignore) {
            if (isset($installedPatches[$package->getName()][$package_name])) {
              $installedPatches[$package->getName()][$package_name] = array_diff($installedPatches[$package->getName()][$package_name], $patches_to_ignore);
            }
          }
        }
        $patches = isset($extra['patches']) ? $extra['patches'] : array();
        $this->patches_temp_list = $this->arrayMergeRecursiveDistinct($this->patches_temp_list, $patches);
      }
      $this->installedPatches = $installedPatches;
      $this->io->write('<info>Second Pass: dependency composer.json patches ignored.</info>');
      $this->writePatchLog('patches-ignore', $full_patches_ignore, 'Full list of patches to ignore from all packages');
      $this->writePatchLog('patches', $installedPatches, 'Final list of patches to be applied');
    }
  }

  /**
   * Checks if the root package enables sub-package patching.
   *
   * @return bool
   *   Whether sub-package patching is enabled. Defaults to TRUE.
   */
  protected function isPackagePatchingEnabled() {
    $extra = $this->composer->getPackage()->getExtra();

    if (empty($extra['patches']) && empty($extra['patches-ignore']) && !isset($extra['patches-file'])) {
      return isset($extra['enable-patches-ignore-subpackages']) ? $extra['enable-patches-ignore-subpackages'] : FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Checks to see if we have any Patches to Ignore from the whitelist.
   *
   * @return bool
   *   Whether the whitelist exists or not.
   */
  protected function checkPatchesIgnoreWhitelist() {
    $extra = $this->composer->getPackage()->getExtra();

    if (empty($extra['patches-ignore-whitelist'])) {
      return FALSE;
    } else {
      return TRUE;
    }
  }

  /**
   * Checks to see if the patches being ignored are in fact legal to use.
   *
   * @param \Composer\Package\PackageInterface $package
   *
   * @return bool
   */
  protected function checkPatchesIgnoreLegal(PackageInterface $package) {
    if (!$this->isPackagePatchingEnabled()) {
      return FALSE;
    }

    $extra = $package->getExtra();
    $package_patches_ignore = isset($extra['patches-ignore']) ? $extra['patches-ignore'] : array();
    $package_name = $package->getName();
    if ($this->checkPatchesIgnoreWhitelist()) {
      $patches_package_whitelist = $this->getPatchesIgnoreWhitelist();
      if (in_array($package_name, $patches_package_whitelist)) {
        return TRUE;
      } else {
        return FALSE;
      }
    }
    if (!$this->checkPatchesIgnoreWhitelist() && isset($package_patches_ignore)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * flattenPatchesIgnore returns the lowest leaf of a multidimensional array.
   *
   * If you have
   *
   * @param $package_patches_ignore
   *
   * @return array
   */
  protected function flattenPatchesIgnore($package_patches_ignore) {
    if ($this->isMultidimensionalArray($package_patches_ignore)) {
      foreach($package_patches_ignore as $package_name => $patches) {
        if ($this->isMultidimensionalArray($patches)) {
          $this->flattenPatchesIgnore($patches);
        } else {
          return [$package_name => $patches];
        }
      }
    }
    if (isset($patches)) {
      return $patches;
    }
    return $package_patches_ignore;
  }

  /**
   * Method to return the whitelist for the patches-ignore packages.
   *
   * @return array
   */
  protected function getPatchesIgnoreWhitelist() {
    $extra = $this->composer->getPackage()->getExtra();
    return $extra['patches-ignore-whitelist'];
  }

  /**
   * Checks to see if the requested array is multidimensional or not.
   *
   * @param array $array
   *   The array to check.
   * @return bool
   *   TRUE or FALSE return.
   */
  protected function isMultidimensionalArray(array $array):bool
  {
    return is_array($array[array_key_first($array)]);
  }

  /**
   * Writes a patch log for debugging purposes.
   *
   * @param $filename
   * @param array $patches
   * @param string $message
   */
  protected function writePatchLog($filename, array $patches, $message = "Patches applied to this folder") {
    $package = $this->composer->getPackage();

    if ($this->checkPatchLog()) {
      $directory = $this->getPatchLogParameter('location');
      $file_format = strtolower($this->getPatchLogParameter('format'));
      if (!file_exists($directory)) {
        mkdir($directory, 0755);
      }
      switch ($file_format) {
        case 'yml':
        case 'yaml':
          file_put_contents($directory . "/" . $filename . ".yml", yaml_emit($patches, JSON_PRETTY_PRINT));
          break;
        case 'json':
          file_put_contents($directory . "/" . $filename . ".json", json_encode($patches, JSON_PRETTY_PRINT));
          break;
        case 'txt':
        case 'text':
          $output = $message . ":\n\n";
          foreach ($patches as $patch => $patch_info) {
            if (isset($patch_info) && !empty($patch_info)) {
              if ($this->isMultidimensionalArray($patch_info)) {
                $output .= '=== ' . $patch;
                foreach ($this->flattenPatchesIgnore($patch_info) as $flatten_patch => $info) {
                  $output .= ' ' . $flatten_patch . "\n";
                  foreach ($info as $description => $url) {
                    $output .= '   ' . $url . "\n";
                    $output .= '   ' . $description . "\n\n";
                  }
                }
              } else {
                $output .= '=== ' . $patch . "\n";
                foreach ($patch_info as $description => $url) {
                  $output .= '   ' . $url . "\n";
                  $output .= '   ' . $description . "\n\n";
                }
              }
            }
          }
          file_put_contents($directory . "/" . $filename . ".txt", $output);

          // TODO: Add a collapsed set of files here.

          break;
        case 'php':
        default:
          // Raw output
          file_put_contents($directory . "/" . $filename . ".php", var_export($patches, true));
      }
    }
  }

  /**
   * @return bool
   */
  protected function checkPatchLog() {
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches-log'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $parameter
   *
   * @return false|mixed
   */
  protected function getPatchLogParameter($parameter) {
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches-log'][$parameter])) {
      return $extra['patches-log'][$parameter];
    }
    return FALSE;
  }

  /**
   * A recursive search for a needle in an array haystack.
   *
   * @param $needle
   * @param array $haystack
   *
   * @return mixed
   */
  public function recursiveSearch($needle, array $haystack)
  {
    $iterator  = new RecursiveArrayIterator($haystack);
    $recursive = new RecursiveIteratorIterator(
      $iterator,
      RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($recursive as $key => $value) {
      if ($key === $needle) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
