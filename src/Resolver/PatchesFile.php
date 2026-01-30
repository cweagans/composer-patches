<?php

/**
 * @file
 * Contains cweagans\Composer\Resolvers\PatchesFile.
 */

namespace cweagans\Composer\Resolver;

use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;
use InvalidArgumentException;

class PatchesFile extends ResolverBase
{
    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection): void
    {
        $patch_files = $this->plugin->getConfig('patch-files');
        if ($patches_file_path = $this->plugin->getConfig('patches-file')) {
            $patch_files[] = $patches_file_path;
            $this->io->write('<warning>`patches-file` config key is deprecated. Use patch-files list instead.</warning>');
        }
        foreach ($patch_files as $patch_file) {
            if (!file_exists(realpath($patch_file)) && !is_readable(realpath($patch_file))) {
                $this->io->write("<warning>Patches file '$patch_file' is not readable.</warning>");
                continue;
            }

            $this->io->write("  - <info>Resolving patches from patches file '$patch_file'.</info>");
            $patches_file = $this->readPatchesFile($patch_file);

            foreach ($this->findPatchesInJson($patches_file) as $package => $patches) {
                foreach ($patches as $patch) {
                    /** @var Patch $patch */
                    $patch->extra['provenance'] = "patches-file:" . $patch_file;
                    $collection->addPatch($patch);
                }
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
