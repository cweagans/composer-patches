<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\ResolverInterface.
 */

namespace cweagans\Composer\Resolvers;

use Composer\Composer;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use cweagans\Composer\PatchCollection;

interface ResolverInterface
{

    /**
     * ResolverInterface constructor.
     *
     * @param Composer $composer
     *   The current composer object from the main plugin. Used to locate/read
     *   package metadata and configuration.
     * @param IOInterface $io
     *   IO object to use for resolver input/output.
     */
    public function __construct(Composer $composer, IOInterface $io);

    /**
     * Find and add patches to the supplied PatchCollection.
     *
     * Note that in this method, it is safe to assume that the resolver is enabled
     * because this method will never be called if ::isEnabled() returns FALSE.
     *
     * @param PatchCollection $collection
     *   A collection of patches that will eventually be applied.
     * @param PackageEvent $event
     *   The event that's currently being responded to.
     * @return mixed
     */
    public function resolve(PatchCollection $collection, PackageEvent $event);
}
