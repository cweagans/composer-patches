<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\ResolverBase.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

abstract class ResolverBase implements ResolverInterface
{

    /**
     * The main Composer object.
     *
     * @var Composer
     */
    protected $composer;

    /**
     * An array of operations that will be executed during this composer execution.
     *
     * @var IOInterface
     */
    protected $io;

    /**
     * {@inheritDoc}
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function resolve(PatchCollection $collection, PackageEvent $event);

    /**
     * Handles the different patch definition formats and returns a list of Patches.
     *
     * @param array $patches
     *   An array of patch defs from composer.json or a patches file.
     *
     * @return array $patches
     *   An array of Patch objects grouped by package name.
     */
    public function findPatchesInJson($patches)
    {
        // Given an array of patch data (pulled directly from the root composer.json
        // or a patches file), figure out what patch format each package is using and
        // marshall everything into Patch objects.
        foreach ($patches as $package => $patch_defs) {
            if (isset($patch_defs[0]) && is_array($patch_defs[0])) {
                $this->io->write("<info>Using expanded definition format for package {$package}</info>");

                foreach ($patch_defs as $index => $def) {
                    $patch = new Patch();
                    $patch->package = $package;
                    $patch->url = $def['url'];
                    $patch->description = $def['description'];

                    $patches[$package][$index] = $patch;
                }
            } else {
                $this->io->write("<info>Using compact definition format for package {$package}</info>");

                $temporary_patch_list = [];

                foreach ($patch_defs as $description => $url) {
                    $patch = new Patch();
                    $patch->package = $package;
                    $patch->url = $url;
                    $patch->description = $description;

                    $temporary_patch_list[] = $patch;
                }

                $patches[$package] = $temporary_patch_list;
            }
        }

        return $patches;
    }
}
