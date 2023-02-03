<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\RootComposer.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Installer\PackageEvent;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

class RootComposer extends ResolverBase
{
    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection, PackageEvent $event): void
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
}
