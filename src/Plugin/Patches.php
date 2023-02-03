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
use Composer\Util\ProcessExecutor;
use cweagans\Composer\Capability\Downloader\CoreDownloaderProvider;
use cweagans\Composer\Capability\Downloader\DownloaderProvider;
use cweagans\Composer\Capability\Patcher\CorePatcherProvider;
use cweagans\Composer\Capability\Patcher\PatcherProvider;
use cweagans\Composer\Capability\Resolver\CoreResolverProvider;
use cweagans\Composer\Capability\Resolver\ResolverProvider;
use cweagans\Composer\ConfigurablePlugin;
use cweagans\Composer\Downloader;
use cweagans\Composer\Event\PatchEvent;
use cweagans\Composer\Event\PatchEvents;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Patcher;
use cweagans\Composer\Resolver;
use cweagans\Composer\Util;
use InvalidArgumentException;
use Exception;

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
     * @var ?PatchCollection $patchCollection
     */
    protected ?PatchCollection $patchCollection;

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
            'disable-patching' => [
                'type' => 'bool',
                'default' => false,
            ],
            'disable-resolvers' => [
                'type' => 'list',
                'default' => [],
            ],
            'disable-downloaders' => [
                'type' => 'list',
                'default' => [],
            ],
            'disable-patchers' => [
                'type' => 'list',
                'default' => [],
            ],
            'default-patch-depth' => [
                'type' => 'int',
                'default' => 1,
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
        $patchLoader = new Resolver($this->composer, $this->io, $this->getConfig('disable-resolvers'));
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
        $patchLoader = new Resolver($this->composer, $this->io, $this->getConfig('disable-resolvers'));
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
        // Sometimes, patchPackage is called before a patch loading function (for instance, when composer-patches itself
        // is installed -- the pre-install event can't be invoked before this plugin is installed, but the post-install
        // event *can* be. Skipping composer-patches and composer-configurable-plugin ensures that this plugin and its
        // dependency won't cause an error to be thrown when attempting to read from an uninitialized PatchCollection.
        // This also means that neither composer-patches nor composer-configurable-plugin can have patches applied.
        $package = $this->getPackageFromOperation($event->getOperation());
        if (in_array($package->getName(), ['cweagans/composer-patches', 'cweagans/composer-configurable-plugin'])) {
            return;
        }

        // If there aren't any patches, there's nothing to do.
        if (empty($this->patchCollection->getPatchesForPackage($package->getName()))) {
            $this->io->write(
                "No patches found for <info>{$package->getName()}</info>",
                true,
                IOInterface::DEBUG,
            );
            return;
        }

        $downloader = new Downloader($this->composer, $this->io, $this->getConfig('disable-downloaders'));
        $patcher = new Patcher($this->composer, $this->io, $this->getConfig('disable-patchers'));

        $install_path = $this->composer->getInstallationManager()
            ->getInstaller($package->getType())
            ->getInstallPath($package);

        $this->io->write("  - Patching <info>{$package->getName()}</info>");

        foreach ($this->patchCollection->getPatchesForPackage($package->getName()) as $patch) {
            /** @var $patch Patch */

            // Download patch.
            $this->io->write(
                "    - Downloading and applying patch <info>{$patch->url}</info> ({$patch->description})",
                true,
                IOInterface::VERBOSE
            );

            $this->io->write("      - Downloading patch <info>{$patch->url}</info>", true, IOInterface::DEBUG);

            $this->composer->getEventDispatcher()->dispatch(
                PatchEvents::PRE_PATCH_DOWNLOAD,
                new PatchEvent(PatchEvents::PRE_PATCH_DOWNLOAD, $package, $patch)
            );
            $downloader->downloadPatch($patch);
            $this->composer->getEventDispatcher()->dispatch(
                PatchEvents::POST_PATCH_DOWNLOAD,
                new PatchEvent(PatchEvents::POST_PATCH_DOWNLOAD, $package, $patch)
            );

            // Apply patch.
            $this->io->write(
                "      - Applying downloaded patch <info>{$patch->localPath}</info>",
                true,
                IOInterface::DEBUG
            );

            $event = new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $patch);
            $this->composer->getEventDispatcher()->dispatch(PatchEvents::PRE_PATCH_APPLY, $event);
            $patch = $event->getPatch();

            $depth = $patch->depth ??
                Util::getDefaultPackagePatchDepth($patch->package) ??
                $this->getConfig('default-patch-depth');

            $patch->depth = $depth;

            $this->io->write(
                "      - Applying patch <info>{$patch->localPath}</info> (depth: {$patch->depth})",
                true,
                IOInterface::DEBUG
            );

            $status = $patcher->applyPatch($patch, $install_path);
            if ($status === false) {
                throw new Exception("No available patcher was able to apply patch {$patch->url} to {$patch->package}");
            }

            $this->composer->getEventDispatcher()->dispatch(
                PatchEvents::POST_PATCH_APPLY,
                new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $patch)
            );
        }

        $this->io->write(
            "  - All patches for <info>{$package->getName()}</info> have been applied.",
            true,
            IOInterface::DEBUG
        );
        // TODO: Write patch data into lock file.
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
