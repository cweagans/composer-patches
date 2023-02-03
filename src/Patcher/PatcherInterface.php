<?php

namespace cweagans\Composer\Patcher;

use Composer\Composer;
use Composer\IO\IOInterface;
use cweagans\Composer\Patch;

interface PatcherInterface
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
     * Apply a patch.
     *
     * @param Patch $patch
     *   The patch to apply.
     */
    public function apply(Patch $patch): void;
}