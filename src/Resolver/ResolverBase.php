<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\ResolverBase.
 */

namespace cweagans\Composer\Resolver;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

abstract class ResolverBase implements ResolverInterface
{
    /**
     * The main Composer object.
     *
     * @var Composer
     */
    protected Composer $composer;

    /**
     * An array of operations that will be executed during this composer execution.
     *
     * @var IOInterface
     */
    protected IOInterface $io;

    /**
     * An instance of the main plugin class.
     *
     * @var PluginInterface
     */
    protected PluginInterface $plugin;

    /**
     * {@inheritDoc}
     */
    public function __construct(Composer $composer, IOInterface $io, PluginInterface $plugin)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->plugin = $plugin;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function resolve(PatchCollection $collection): void;

    /**
     * Handles the different patch definition formats and returns a list of Patches.
     *
     * @param array $patches
     *   An array of patch defs from composer.json or a patches file.
     *
     * @return array $patches
     *   An array of Patch objects grouped by package name.
     */
    public function findPatchesInJson(array $patches): array
    {
        // Given an array of patch data (pulled directly from the root composer.json
        // or a patches file), figure out what patch format each package is using and
        // marshall everything into Patch objects.
        foreach ($patches as $package => $patch_defs) {
            if (isset($patch_defs[0]) && is_array($patch_defs[0])) {
                $this->io->write(
                    "    Using expanded definition format for package <info>{$package}</info>",
                    true,
                    IOInterface::VERBOSE
                );

                foreach ($patch_defs as $index => $def) {
                    $patch = new Patch();
                    $patch->package = $package;
                    $patch->url = $def['url'];
                    $patch->description = $def['description'];
                    $patch->sha256 = $def['sha256'] ?? null;
                    $patch->depth = $def['depth'] ?? null;
                    $patch->extra = $def['extra'] ?? [];

                    $patches[$package][$index] = $patch;
                }
            } else {
                $this->io->write(
                    "    Using compact definition format for package <info>{$package}</info>",
                    true,
                    IOInterface::VERBOSE
                );

                $temporary_patch_list = [];

                foreach ($patch_defs as $description => $url) {
                    if (is_array($url)) {
                      foreach ($url as $patchdescription => $patchurl) {
                        $temporary_patch_list[] = $this->getPatches($package, $patchdescription, $patchurl, $description);
                      }
                    }
                    else {
                      $temporary_patch_list[] = $this->getPatches($package, $description, $url);
                    }
                }
                $patches[$package] = $temporary_patch_list;
            }
        }

        return $patches;
    }

    /**
     * Helper function to create patch object.
     *
     * @param array $package
     *   The package name.
     * @param array $description
     *   The patch description.
     * @param string $url
     *   The patch url.
     * @param string $version
     *   The version of package.
     *
     * @return Patch $patches
     *   An array of Patch objects.
     */
    public function getPatches(string $package, string $description, string $url, string $version = ''): Patch
    {
        $patch = new Patch();
        $patch->package = $package;
        $patch->url = $url;
        $patch->description = $description;
        if (!empty($version)) {
            $patch->version = $version;
        }

        return $patch;
    }
}
