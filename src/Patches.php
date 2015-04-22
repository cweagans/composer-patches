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
      PackageEvents::POST_PACKAGE_INSTALL => "postInstall",
      PackageEvents::POST_PACKAGE_UPDATE => "postInstall"
    ];
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
    if (isset($extra['patches']) && isset($extra['patches'][$package_name])) {
      $this->io->write('<comment>No patches found.</comment>');
      return;
    }

    foreach ($extra['patches'][$package_name] as $description => $url) {
      $message = '<comment>' . $description . ' (fetching from ' . $url . ')</comment>';
      $this->io->write($message);
    }


    // Get the install path from the package object.
//    $manager = $event->getComposer()->getInstallationManager();
//    $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);


  }

}
