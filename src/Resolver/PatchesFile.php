<?php

/**
 * @file
 * Contains cweagans\Composer\Resolvers\PatchesFile.
 */

namespace cweagans\Composer\Resolver;

use cweagans\Composer\Patch;
use cweagans\Composer\Plugin\Patches;
use cweagans\Composer\PatchCollection;
use InvalidArgumentException;

class PatchesFile extends ResolverBase
{
    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection): void
    {
        $patches_file = $this->plugin->getConfig('patches-file');

        $valid_patches_file = file_exists(realpath($patches_file)) && is_readable(realpath($patches_file));

        // $extra = $this->composer->getPackage()->getExtra();
        // $valid_patches_file = array_key_exists('composer-patches', $extra) &&
        //     array_key_exists('patches-file', $extra['composer-patches']) &&
        //     file_exists(realpath($extra['composer-patches']['patches-file'])) &&
        //     is_readable(realpath($extra['composer-patches']['patches-file']));

        // If we don't have a valid patches file, exit early.
        if (!$valid_patches_file) {
            return;
        }

        $this->io->write('  - <info>Resolving patches from patches file.</info>');
        $patches_file = $this->readPatchesFile($patches_file);

        foreach ($this->findPatchesInJson($patches_file) as $package => $patches) {
            foreach ($patches as $patch) {
                /** @var Patch $patch */
                $collection->addPatch($patch);
            }
        }
    }

    /**
     * Read a patches file.
     *
     * @param $patches_file
     *   A URI to a file. Can be anything accepted by file_get_contents().
     * @return array
     *   A list of patches.
     * @throws InvalidArgumentException
     */
    protected function readPatchesFile($patches_file): array
    {
        if ($patches_file === '') {
            return [];
        }

        $patches = file_get_contents($patches_file);
        $patches = json_decode($patches, true);

        // First, check for JSON syntax issues.
        $json_error = json_last_error_msg();
        if ($json_error !== "No error") {
            throw new InvalidArgumentException($json_error);
        }

        // Next, make sure there is a patches key in the file.
        if (!array_key_exists('patches', $patches)) {
            throw new InvalidArgumentException('No patches found.');
        }

        // If nothing is wrong at this point, we can return the list of patches.
        return $patches['patches'];
    }
}
