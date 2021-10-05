<?php

/**
 * @file
 * For Patches Ignore functionality to piggy-back onto the main class.
 */

namespace cweagans\Composer;

use Composer\IO\IOInterface;
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

  /**
   * @var array $patches_flattened
   */
  protected $patches_flattened;

  /**
   * @var array $patches_flattened
   */
  protected $patches_ignore_flattened;

  /**
   * Do the Patches Ignore collation.
   *
   * @param array|null $tmp_patches
   */
  public function doPatchesIgnoreCollation(array &$tmp_patches = NULL) {
    if ($tmp_patches === NULL) {
      $tmp_patches = $this->patches;
    }
    if ($this->isPackagePatchingEnabled()) {
      $this->io->write('<info>Gathering patches ignore from dependencies. This may take a moment, please stand by...</info>');
      foreach ($this->packages as $package) {
        $extra = $package->getExtra();
        // Review patches-ignore legality of the package per settings.
        if ($this->checkPatchesIgnoreLegal($package) && isset($extra['patches-ignore'])) {
          $this->patches_ignore_flattened = $this->arrayMergeRecursiveDistinct($this->patches_ignore_flattened, $extra['patches-ignore']);
          $this->io->write('<info>Package ' . $package->getName() . ' has patches-ignore.</info>', TRUE, IOInterface::VERBOSE);
          // Apply the package composer.json patches-ignore list.
          $flattened_patches_ignore = $this->flattenPatchesIgnore($extra['patches-ignore']);
          foreach ($flattened_patches_ignore as $package_name => $patches_to_ignore) {
            $this->io->write('<comment> - preparing patches-ignore for ' . $package_name . ':</comment>', TRUE, IOInterface::VERBOSE);
            if (isset($tmp_patches[$package_name])) {
              $tmp_patches[$package_name] = array_diff($tmp_patches[$package_name], $patches_to_ignore);
            }
          }
        }
      }
      // If the patches array is empty, we're in CheckPatches step, otherwise
      // set the patches variable as we're in the GatherPatches step.
      if (!empty($this->patches)) {
        $this->patches = $tmp_patches;
      }
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

    if (isset($extra['enable-patches-ignore-subpackages']) && $extra['enable-patches-ignore-subpackages']) {
      return TRUE;
    } else {
      if (!empty($extra['patches']) || !empty($extra['patches-ignore']) || isset($extra['patches-file'])) {
        return TRUE;
      }
      else {
        return FALSE;
      }
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
   * @return array|bool
   */
  protected function flattenPatchesIgnore($package_patches_ignore) {
    if (!is_array($package_patches_ignore)) {
      return FALSE;
    }
    $result = array();
    foreach ($package_patches_ignore as $key => $value) {
      if ($this->isMultidimensionalArray($value)) {
        $result = array_merge($result, $this->flattenPatchesIgnore($value));
      }
      else {
        $result[$key] = $value;
      }
    }
    return $result;
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
  protected function isMultidimensionalArray(array $array)
  {
    if (array_key_first($array) === null) {
      return FALSE;
    }
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
