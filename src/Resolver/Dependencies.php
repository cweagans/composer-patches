<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\Dependencies.
 */

namespace cweagans\Composer\Resolver;

use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

class Dependencies extends ResolverBase
{
    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection): void
    {
        $locker = $this->composer->getLocker();
        if (!$locker->isLocked()) {
            $this->io->write('  - <info>Composer lock file does not exist.</info>');
            $this->io->write('  - <info>Patches defined in dependencies will not be resolved.</info>');
            return;
        }

        // Using both config keys, if $allowed_dependencies is set, it takes precedence
        $allowed_dependencies = $this->plugin->getConfig('allow-dependency-patches');
        $ignored_dependencies = $this->plugin->getConfig('ignore-dependency-patches');

        // First check, if we do allow dependency patches at all.
        if ($allowed_dependencies === []) {
            $this->io->write('  - <info>No patches from dependencies are allowed.</info>');
            return;
        }

        $this->io->write('  - <info>Resolving patches from dependencies.</info>');

        $lockdata = $locker->getLockData();
        foreach ($lockdata['packages'] as $p) {
            $allowed = in_array($p['name'], $allowed_dependencies ?? []);
            $ignored = in_array($p['name'], $ignored_dependencies);

            // Allowed dependencies is not set in composer.json, and we're not
            // supposed to skip gathering patches from this dependency
            if (is_null($allowed_dependencies) && !$ignored) {
                $this->lookForPatches($p, $collection);
            }

            // Allowed dependencies are set, act only on allowed, if they're not
            // ignored also.
            if ($allowed && !$ignored) {
                $this->lookForPatches($p, $collection);
            }
        }
    }

    private function lookForPatches(array $p, PatchCollection $collection): void
    {

        // Find patches in the composer.json for dependencies.
        if (isset($p['extra']['patches'])) {
            foreach ($this->findPatchesInJson($p['extra']['patches']) as $package => $patches) {
                foreach ($patches as $patch) {
                    $patch->extra['provenance'] = "dependency:" . $package;

                    /** @var Patch $patch */
                    $collection->addPatch($patch);
                }
            }
        }

        // Find patches in a patch-file for dependencies.
        if (isset($p['extra']['composer-patches']['patches-file'])) {
            // @todo Handle patch files.
        }
    }
}
