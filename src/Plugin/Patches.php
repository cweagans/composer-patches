<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventDispatcher;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;
use Composer\Util\ProcessExecutor;
use cweagans\Composer\Capability\Patcher\CorePatcherProvider;
use cweagans\Composer\Capability\Patcher\PatcherProvider;
use cweagans\Composer\Capability\Resolver\CoreResolverProvider;
use cweagans\Composer\Capability\Resolver\ResolverProvider;
use cweagans\Composer\ConfigurablePlugin;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\PatchLoader;

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
            ScriptEvents::PRE_INSTALL_CMD => ['loadLockedPatches'],
            ScriptEvents::PRE_UPDATE_CMD => ['loadPatchesFromResolvers'],
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
            PatcherProvider::class => CorePatcherProvider::class,
        ];
    }

    /**
     * Discover patches using all available Resolvers.
     *
     * @param ScriptEvent $event
     *   The event provided by Composer.
     */
    public function loadPatchesFromResolvers(ScriptEvent $event)
    {
        $patchLoader = new PatchLoader($this->composer, $this->io, $this->getConfig('disable-resolvers'));
        $this->patchCollection = $patchLoader->loadFromResolvers();
    }

    /**
     * Load previously discovered patches from the Composer lock file.
     *
     * @param ScriptEvent $event
     *   The event provided by Composer.
     */
    public function loadLockedPatches(ScriptEvent $event)
    {
        $patchLoader = new PatchLoader($this->composer, $this->io, $this->getConfig('disable-resolvers'));
        $this->patchCollection = $patchLoader->loadFromLock();
    }

    public function patchPackage(PackageEvent $event)
    {
        // Download patch.
        // Apply patch to package.
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
