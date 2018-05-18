<?php

/**
 * @file
 * Contains cweagans\Composer\Resolvers\PatchesFile.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Installer\PackageEvent;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;

class PatchesFile extends ResolverBase
{

    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection, PackageEvent $event)
    {
        $this->io->write('  - <info>Gathering patches from patches file.</info>');

        $extra = $this->composer->getPackage()->getExtra();
        $valid_patches_file = array_key_exists('patches-file', $extra) &&
            file_exists(realpath($extra['patches-file'])) &&
            is_readable(realpath($extra['patches-file']));

        // If we don't have a valid patches file, exit early.
        if (!$valid_patches_file) {
            return;
        }

        $patches_file = $this->readPatchesFile($extra['patches-file']);

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
     * @throws \InvalidArgumentException
     */
    protected function readPatchesFile($patches_file)
    {
        $patches = file_get_contents($patches_file);
        $patches = json_decode($patches, true);

        // First, check for JSON syntax issues.
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            switch ($error) {
                case JSON_ERROR_SYNTAX:
                    $msg = 'Syntax error, malformed JSON.';
                    break;
                // Because we don't care about testing PHP's JSON error handling
                // in great detail, we're going to ignore the other cases for the
                // purposes of code coverage reporting.
                // @codeCoverageIgnoreStart
                case JSON_ERROR_DEPTH:
                    $msg = 'Maximum stack depth exceeded.';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $msg = 'Underflow or the modes mismatch.';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $msg = 'Unexpected control character found.';
                    break;
                case JSON_ERROR_UTF8:
                    $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
                    break;
                default:
                    $msg = 'Unknown error.';
                    break;
                // @codeCoverageIgnoreEnd
            }

            throw new \InvalidArgumentException($msg);
        }

        // Next, make sure there is a patches key in the file.
        if (!array_key_exists('patches', $patches)) {
            throw new \InvalidArgumentException('No patches found.');
        }

        // If nothing is wrong at this point, we can return the list of patches.
        return $patches['patches'];
    }
}
