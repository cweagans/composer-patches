<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

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
   * Apply plugin modifications to composer
   *
   * @param Composer    $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   */
  public static function getSubscribedEvents() {
    return [
      ScriptEvents::POST_INSTALL_CMD => "onInstallOrUpdate",
      ScriptEvents::POST_UPDATE_CMD => "onInstallOrUpdate",
    ];
  }

  /**
   * @param Event $event
   */
  public function onInstallOrUpdate(Event $event) {
    $extra = $this->composer->getPackage()->getExtra();
    if (isset($extra['patches'])) {
      $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getPackages();
      foreach ($packages as $package) {
        /**
         * @var PackageInterface $package
         */
        $package_name = $package->getName();
        if (isset($extra['patches'][$package_name])) {
          $this->io->write('<info>Found patches for ' . $package_name . '</info>');
//          $install_path = $this->composer->getInstallationManager()->getInstallPath($package);
        }
      }
    }
  }

}
