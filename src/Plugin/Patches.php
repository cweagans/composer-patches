<?php

/**
 * @file
 * Provides a way to patch Composer packages after installation.
 */

namespace cweagans\Composer\Plugin;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\EventDispatcher\EventDispatcher;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvent;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use cweagans\Composer\Capability\ResolverProvider;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Resolvers\ResolverBase;
use cweagans\Composer\Resolvers\ResolverInterface;
use Symfony\Component\Process\Process;
use cweagans\Composer\Util;
use cweagans\Composer\PatchEvent;
use cweagans\Composer\PatchEvents;
use cweagans\Composer\ConfigurablePlugin;
use cweagans\Composer\Patch;

class Patches implements PluginInterface, EventSubscriberInterface, Capable
{

    use ConfigurablePlugin;

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
     * @var bool $patchesResolved
     */
    protected $patchesResolved;

    /**
     * @var PatchCollection $patchCollection
     */
    protected $patchCollection;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->eventDispatcher = $composer->getEventDispatcher();
        $this->executor = new ProcessExecutor($this->io);
        $this->patches = array();
        $this->installedPatches = array();
        $this->patchesResolved = false;
        $this->patchCollection = new PatchCollection();

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
     */
    public static function getSubscribedEvents()
    {
        return array(
//            ScriptEvents::PRE_INSTALL_CMD => array('checkPatches'),
//            ScriptEvents::PRE_UPDATE_CMD => array('checkPatches'),
            PackageEvents::PRE_PACKAGE_INSTALL => array('resolvePatches'),
            PackageEvents::PRE_PACKAGE_UPDATE => array('resolvePatches'),
            // The following is a higher weight for compatibility with
            // https://github.com/AydinHassan/magento-core-composer-installer and
            // more generally for compatibility with any Composer plugin which
            // deploys downloaded packages to other locations. In the case that
            // you want those plugins to deploy patched files, those plugins have
            // to run *after* this plugin.
            // @see: https://github.com/cweagans/composer-patches/pull/153
            PackageEvents::POST_PACKAGE_INSTALL => array('postInstall', 10),
            PackageEvents::POST_PACKAGE_UPDATE => array('postInstall', 10),
        );
    }

    /**
     * Return a list of plugin capabilities.
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'cweagans\Composer\Capability\ResolverProvider' => 'cweagans\Composer\Capability\CoreResolverProvider',
        ];
    }

    /**
     * Gather a list of all patch resolvers from all enabled Composer plugins.
     *
     * @return ResolverBase[]
     *   A list of PatchResolvers to be run.
     */
    public function getPatchResolvers()
    {
        $resolvers = [];
        $plugin_manager = $this->composer->getPluginManager();
        foreach ($plugin_manager->getPluginCapabilities(
            'cweagans\Composer\Capability\ResolverProvider',
            ['composer' => $this->composer, 'io' => $this->io]
        ) as $capability) {
            /** @var ResolverProvider $capability */
            $newResolvers = $capability->getResolvers();
            if (!is_array($newResolvers)) {
                throw new \UnexpectedValueException(
                    'Plugin capability ' . get_class($capability) . ' failed to return an array from getResolvers().'
                );
            }
            foreach ($newResolvers as $resolver) {
                if (!$resolver instanceof ResolverBase) {
                    throw new \UnexpectedValueException(
                        'Plugin capability ' . get_class($capability) . ' returned an invalid value.'
                    );
                }
            }
            $resolvers = array_merge($resolvers, $newResolvers);
        }

        return $resolvers;
    }

    /**
     * Gather patches that need to be applied to the current set of packages.
     *
     * Note that this work is done unconditionally if this plugin is enabled,
     * even if patching is disabled in any way. The point where patches are applied
     * is where the work will be skipped. It's done this way to ensure that
     * patching can be disabled temporarily in a way that doesn't affect the
     * contents of composer.lock.
     *
     * @param PackageEvent $event
     *   The PackageEvent passed by Composer
     */
    public function resolvePatches(PackageEvent $event)
    {
        // No need to resolve patches more than once.
        if ($this->patchesResolved) {
            return;
        }

        // Let each resolver discover patches and add them to the PatchCollection.
        /** @var ResolverInterface $resolver */
        foreach ($this->getPatchResolvers() as $resolver) {
            if (!in_array(get_class($resolver), $this->getConfig('disable-resolvers'))) {
                $resolver->resolve($this->patchCollection, $event);
            } else {
                if ($this->io->isVerbose()) {
                    $this->io->write('<info>  - Skipping resolver ' . get_class($resolver) . '</info>');
                }
            }
        }

        // Make sure we only do this once.
        $this->patchesResolved = true;
    }

    /**
     * Before running composer install,
     * @param Event $event
     */
    public function checkPatches(Event $event)
    {
        if (!$this->isPatchingEnabled()) {
            return;
        }

        try {
            $repositoryManager = $this->composer->getRepositoryManager();
            $localRepository = $repositoryManager->getLocalRepository();
            $installationManager = $this->composer->getInstallationManager();
            $packages = $localRepository->getPackages();

            $tmp_patches = $this->grabPatches();
            foreach ($packages as $package) {
                $extra = $package->getExtra();
                if (isset($extra['patches'])) {
                    $this->installedPatches[$package->getName()] = $extra['patches'];
                }
                $patches = isset($extra['patches']) ? $extra['patches'] : array();
                $tmp_patches = Util::arrayMergeRecursiveDistinct($tmp_patches, $patches);
            }

            if ($tmp_patches == false) {
                $this->io->write('<info>No patches supplied.</info>');
                return;
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
                        || ($has_patches && $has_applied_patches &&
                            $tmp_patches[$package_name] !== $extra['patches_applied'])) {
                        $uninstallOperation = new UninstallOperation(
                            $package,
                            'Removing package so it can be re-installed and re-patched.'
                        );
                        $this->io->write('<info>Removing package ' .
                            $package_name .
                            ' so that it can be re-installed and re-patched.</info>');
                        $installationManager->uninstall($localRepository, $uninstallOperation);
                    }
                }
            }
        } catch (\LogicException $e) {
            // If the Locker isn't available, then we don't need to do this.
            // It's the first time packages have been installed.
            return;
        }
    }

    /**
     * Check whether a given path is relative.
     *
     * @param string $url
     * @return bool
     */
    protected function isRelativeUrl($url) {
        if (parse_url($url, PHP_URL_SCHEME) != '') {
            return FALSE;
        }

        if ($url[0] == '/') {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param PackageEvent $event
     * @throws \Exception
     */
    public function postInstall(PackageEvent $event)
    {
        // Get the package object for the current operation.
        $operation = $event->getOperation();
        /** @var PackageInterface $package */
        $package = $this->getPackageFromOperation($operation);
        $package_name = $package->getName();

        if (empty($this->patchCollection->getPatchesForPackage($package_name))) {
            if ($this->io->isVerbose()) {
                $this->io->write('<info>No patches found for ' . $package_name . '.</info>');
            }
            return;
        }
        $this->io->write('  - Applying patches for <info>' . $package_name . '</info>');

        // Get the install path from the package object.
        $manager = $event->getComposer()->getInstallationManager();
        $install_path = $manager->getInstaller($package->getType())->getInstallPath($package);

        // Set up a downloader.
        $downloader = new RemoteFilesystem($this->io, $this->composer->getConfig());

        // Track applied patches in the package info in installed.json
        $localRepository = $this->composer->getRepositoryManager()->getLocalRepository();
        $localPackage = $localRepository->findPackage($package_name, $package->getVersion());
        $extra = $localPackage->getExtra();
        $extra['patches_applied'] = array();

        foreach ($this->patchCollection->getPatchesForPackage($package_name) as $patch) {
            /** @var Patch $patch */
            $this->io->write('    <info>' . $patch->url . '</info> (<comment>' . $patch->description . '</comment>)');
            try {
                $this->eventDispatcher->dispatch(
                    null,
                    new PatchEvent(PatchEvents::PRE_PATCH_APPLY, $package, $patch->url, $patch->description)
                );

                if ($this->isRelativeUrl($patch->url) && !empty($patch->provider)) {
                  if (!$manager->isPackageInstalled($localRepository, $patch->provider)) {
                    $this->io->write(' - <info>Installing '.$patch->provider->getName().'</info> to ensure access to relative patches.');
                    $manager->getInstaller($patch->provider->getType())->install($localRepository, $patch->provider);
                  }
                  $providing_install_path = $manager->getInstallPath($patch->provider);
                  $patch->url = $providing_install_path.'/'.$patch->url;
                }

                $this->getAndApplyPatch($downloader, $install_path, $patch->url);
                $this->eventDispatcher->dispatch(
                    null,
                    new PatchEvent(PatchEvents::POST_PATCH_APPLY, $package, $patch->url, $patch->description)
                );
                $extra['patches_applied'][$patch->description] = $patch->url;
            } catch (\Exception $e) {
                $this->io->write(
                    '   <error>Could not apply patch! Skipping. The error was: ' .
                    $e->getMessage() .
                    '</error>'
                );
                if ($this->getConfig('exit-on-patch-failure')) {
                    throw new \Exception("Cannot apply patch $patch->description ($patch->url)!");
                }
            }
        }
//        $localPackage->setExtra($extra);
    }

    /**
     * Get a Package object from an OperationInterface object.
     *
     * @param OperationInterface $operation
     * @return PackageInterface
     * @throws \Exception
     */
    protected function getPackageFromOperation(OperationInterface $operation)
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
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
    protected function getAndApplyPatch(RemoteFilesystem $downloader, $install_path, $patch_url)
    {

        // Local patch file.
        if (file_exists($patch_url)) {
            $filename = realpath($patch_url);
        } else {
            // Generate random (but not cryptographically so) filename.
            $filename = uniqid(sys_get_temp_dir() . '/') . ".patch";

            // Download file from remote filesystem to this location.
            $hostname = parse_url($patch_url, PHP_URL_HOST);
            $downloader->copy($hostname, $patch_url, $filename, false);
        }

        // Modified from drush6:make.project.inc
        $patched = false;
        // The order here is intentional. p1 is most likely to apply with git apply.
        // p0 is next likely. p2 is extremely unlikely, but for some special cases,
        // it might be useful. p4 is useful for Magento 2 patches
        $patch_levels = $this->getConfig('patch-levels');
        foreach ($patch_levels as $patch_level) {
            if ($this->io->isVerbose()) {
                $comment = 'Testing ability to patch with git apply.';
                $comment .= ' This command may produce errors that can be safely ignored.';
                $this->io->write('<comment>' . $comment . '</comment>');
            }
            $checked = $this->executeCommand(
                'git -C %s apply --check -v %s %s',
                $install_path,
                $patch_level,
                $filename
            );
            $output = $this->executor->getErrorOutput();
            if (substr($output, 0, 7) == 'Skipped') {
                // Git will indicate success but silently skip patches in some scenarios.
                //
                // @see https://github.com/cweagans/composer-patches/pull/165
                $checked = false;
            }
            if ($checked) {
                // Apply the first successful style.
                $patched = $this->executeCommand(
                    'git -C %s apply %s %s',
                    $install_path,
                    $patch_level,
                    $filename
                );
                break;
            }
        }

        // In some rare cases, git will fail to apply a patch, fallback to using
        // the 'patch' command.
        if (!$patched) {
            foreach ($patch_levels as $patch_level) {
                // --no-backup-if-mismatch here is a hack that fixes some
                // differences between how patch works on windows and unix.
                if ($patched = $this->executeCommand(
                    "patch %s --no-backup-if-mismatch -d %s < %s",
                    $patch_level,
                    $install_path,
                    $filename
                )
                ) {
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
     *   Whether patching is enabled. Defaults to true.
     */
    protected function isPatchingEnabled()
    {
        $enabled = true;

        $has_no_patches = empty($extra['patches']);
        $has_no_patches_file = ($this->getConfig('patches-file') == '');
        $patching_disabled = $this->getConfig('disable-patching');

        if ($patching_disabled || !($has_no_patches && $has_no_patches_file)) {
            $enabled = false;
        }

        return $enabled;
    }

    /**
     * Executes a shell command with escaping.
     *
     * @param string $cmd
     * @return bool
     */
    protected function executeCommand($cmd)
    {
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
                } else {
                    $io->write('<comment>' . $data . '</comment>');
                }
            };
        }
        return ($this->executor->execute($command, $output) == 0);
    }
}
