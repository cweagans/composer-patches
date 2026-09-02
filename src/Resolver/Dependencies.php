<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\Dependencies.
 */

namespace cweagans\Composer\Resolver;

use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Pcre\Preg;
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

        $this->io->write('  - <info>Resolving patches from dependencies.</info>');

        $ignored_dependencies = $this->plugin->getConfig('ignore-dependency-patches');
        $allowed_dependencies = $this->plugin->getConfig('allow-dependency-patches');

        $allowed_dependencies_regex =
            $allowed_dependencies ? static::packageNamesToRegexp($allowed_dependencies) : null;
        $ignored_dependencies_regex =
            $ignored_dependencies ? static::packageNamesToRegexp($ignored_dependencies) : null;

        $lockdata = $locker->getLockData();
        foreach ($lockdata['packages'] as $p) {
            // Find patches in the composer.json for dependencies.
            if (!isset($p['extra']['patches'])) {
                continue;
            }
            $package_name = $p['name'];
            // Always skip gathering patches from an ignored dependency.
            if ($ignored_dependencies_regex) {
                if (Preg::match($ignored_dependencies_regex, $package_name)) {
                    $this->io->write(
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "  - Skipping patches from the <info>$package_name</info> dependency because it is within the <comment>ignore-dependency-patches</comment> list.</info>",
                        true,
                        IOInterface::DEBUG,
                    );
                    continue;
                }
            }

            if ($allowed_dependencies_regex) {
                // If a list of allowed packages is specified, then ensure that
                // only packages within the list are gathered.
                if (!Preg::match($allowed_dependencies_regex, $package_name)) {
                    $this->io->write(
                        // phpcs:ignore Generic.Files.LineLength.TooLong
                        "  - Skipping patches from the <info>$package_name</info> dependency because it is not within the <comment>allow-dependency-patches</comment> list.",
                        true,
                        IOInterface::DEBUG,
                    );
                    continue;
                }
            }
            // Find patches in the composer.json for dependencies.
            foreach ($this->findPatchesInJson($p['extra']['patches']) as $patches) {
                foreach ($patches as $patch) {
                    $patch->extra['provenance'] = "dependency:" . $package_name;

                    /** @var Patch $patch */
                    $collection->addPatch($patch);
                }
            }

            // TODO: Also find patches in a configured patches.json for the dependency.
        }
    }

    /**
     * Build a regexp from package names, expanding * globs as required.
     *
     * @param string[] $packageNames
     * @return non-empty-string
     *
     * @see \Composer\Package\BasePackage::packageNamesToRegexp()
     *   Reimplemented here to support composer-plugin-api 2.0/2.1.
     */
    protected static function packageNamesToRegexp(array $packageNames): string
    {
        $packageNames = array_map(
            static function ($packageName): string {
                return BasePackage::packageNameToRegexp($packageName, '%s');
            },
            $packageNames
        );

        return sprintf('{^(?:%s)$}iD', implode('|', $packageNames));
    }
}
