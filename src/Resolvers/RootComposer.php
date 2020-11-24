<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\RootComposer.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Installer\PackageEvent;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\PatchOptionsCollection;

class RootComposer extends ResolverBase
{
    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection, PackageEvent $event)
    {
        $this->io->write('  - <info>Gathering patches from root package</info>');

        $extra = $this->composer->getPackage()->getExtra();

        if (empty($extra['patches'])) {
            return;
        }

        foreach ($this->findPatchesInJson($extra['patches']) as $package => $patches) {
            foreach ($patches as $patch) {
                /** @var Patch $patch */
                $collection->addPatch($patch);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function resolveOptions(PatchOptionsCollection $options_collection, PackageEvent $event)
    {
        $extra = $this->composer->getPackage()->getExtra();
        if (empty($extra['patches-options'])) {
            return;
        }
        foreach ($this->findPatchesOptionsInJson($extra['patches-options']) as $package => $patches_options) {
            foreach ($patches_options as $patch_options) {
                /** @var Patch $patch */
                $options_collection->addOptions($patch_options);
            }
        }
    }
}
