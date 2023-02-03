<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer\Plugin;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventDispatcher;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;
use cweagans\Composer\Capability\Downloader\CoreDownloaderProvider;
use cweagans\Composer\Capability\Downloader\DownloaderProvider;
use cweagans\Composer\Capability\Patcher\CorePatcherProvider;
use cweagans\Composer\Capability\Patcher\PatcherProvider;
use cweagans\Composer\Capability\Resolver\CoreResolverProvider;
use cweagans\Composer\Capability\Resolver\ResolverProvider;
use cweagans\Composer\ConfigurablePlugin;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\PatchDownloader;
use cweagans\Composer\PatchLoader;
use InvalidArgumentException;

class Patches implements PluginInterface, EventSubscriberInterface, Capable
{
    use ConfigurablePlugin;

    /**
     * @var Composer $composer
     */
    protected Composer $composer;

    /**
     * @var IOInterface $io
     */
    protected IOInterface $io;

    /**
     * @var EventDispatcher $eventDispatcher
     */
    protected EventDispatcher $eventDispatcher;

    /**
     * @var ProcessExecutor $executor
     */
    protected ProcessExecutor $executor;

    /**
     * @var array $patches
     */
    protected array $patches;

    /**
     * @var array $installedPatches
     */
    protected array $installedPatches;

    /**
     * @var PatchCollection $patchCollection
     */
    protected PatchCollection $patchCollection;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->executor = new ProcessExecutor($this->io);
        $this->patches = array();
        $this->installedPatches = array();
        $this->configuration = [
            'exit-on-patch-failure' => [
                'type' => 'bool',
                'default' => true,
            ],
            'disable-patching' => [
                'type' => 'bool',
                'default' => false,
            ],
            'disable-resolvers' => [
                'type' => 'list',
                'default' => [],
            ],
            'patch-levels' => [
                'type' => 'list',
                'default' => ['-p1', '-p0', '-p2', '-p4']
            ],
            'patches-file' => [
                'type' => 'string',
                'default' => '',
            ]
        ];
        $this->configure($this->composer->getPackage()->getExtra(), 'composer-patches');
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @calls resolvePatches
     */
    public static function getSubscribedEvents(): array
    {
        return array(
            PackageEvents::PRE_PACKAGE_INSTALL => ['loadLockedPatches'],
            PackageEvents::PRE_PACKAGE_UPDATE => ['loadPatchesFromResolvers'],
            // The POST_PACKAGE_* events are a higher weight for compatibility with
            // https://github.com/AydinHassan/magento-core-composer-installer and more generally for compatibility with
            // any Composer Plugin which deploys downloaded packages to other locations. In the cast that you want
            // those plugins to deploy patched files, those plugins have to run *after* this plugin.
            // @see: https://github.com/cweagans/composer-patches/pull/153
            PackageEvents::POST_PACKAGE_INSTALL => ['patchPackage', 10],
            PackageEvents::POST_PACKAGE_UPDATE => ['patchPackage', 10],
//            ScriptEvents::PRE_INSTALL_CMD => array('checkPatches'),
//            ScriptEvents::PRE_UPDATE_CMD => array('checkPatches'),
//            PackageEvents::POST_PACKAGE_INSTALL => array('postInstall', 10),
//            PackageEvents::POST_PACKAGE_UPDATE => array('postInstall', 10),
        );
    }

    /**
     * Return a list of plugin capabilities.
     *
     * @return array
     */
    public function getCapabilities(): array
    {
        return [
            ResolverProvider::class => CoreResolverProvider::class,
            DownloaderProvider::class => CoreDownloaderProvider::class,
            PatcherProvider::class => CorePatcherProvider::class,
        ];
    }

    /**
     * Discover patches using all available Resolvers.
     *
     * @param ScriptEvent $event
     *   The event provided by Composer.
     */
    public function loadPatchesFromResolvers(PackageEvent $event)
    {
        $this->io->write("loadPatchesFromResolvers called");
        if (!is_null($this->patchCollection)) {
            return;
        }

        $patchLoader = new PatchLoader($this->composer, $this->io, $this->getConfig('disable-resolvers'));
        $this->patchCollection = $patchLoader->loadFromResolvers();
    }

    /**
     * Load previously discovered patches from the Composer lock file.
     *
     * @param ScriptEvent $event
     *   The event provided by Composer.
     */
    public function loadLockedPatches(PackageEvent $event)
    {
        $this->io->write("loadLockedPatches called");
        if (!is_null($this->patchCollection)) {
            return;
        }

        $patchLoader = new PatchLoader($this->composer, $this->io, $this->getConfig('disable-resolvers'));
//        $this->patchCollection = $patchLoader->loadFromLock();
        $this->patchCollection = $patchLoader->loadFromResolvers();
    }

    /**
     * Download and apply patches.
     *
     * @param PackageEvent $event
     *   The event that Composer provided to us.
     */
    public function patchPackage(PackageEvent $event)
    {
        $this->io->write("patchPackage called");
        $package = $this->getPackageFromOperation($event->getOperation());
        $downloader = new PatchDownloader($this->composer, $this->io);

        // Download all patches for the package.
        foreach ($this->patchCollection->getPatchesForPackage($package->getName()) as $patch) {
            /** @var $patch Patch */
            $this->io->write("Downloading patch " . $patch->url);
            $downloader->downloadPatch($patch);
        }
        // Apply patch to package.
    }

    /**
     * Get a Package object from an OperationInterface object.
     *
     * @param OperationInterface $operation
     * @return PackageInterface
     * @throws InvalidArgumentException
     */
    protected function getPackageFromOperation(OperationInterface $operation): PackageInterface
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            throw new InvalidArgumentException('Unknown operation: ' . get_class($operation));
        }

        return $package;
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
