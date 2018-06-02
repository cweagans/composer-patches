<?php

namespace cweagans\Composer\Capability;

use cweagans\Composer\Resolvers\DependencyPatches;
use cweagans\Composer\Resolvers\PatchesFile;
use cweagans\Composer\Resolvers\RootComposer;

class CoreResolverProvider extends BaseResolverProvider
{
    /**
     * {@inheritDoc}
     */
    public function getResolvers()
    {
        return [
            new RootComposer($this->composer, $this->io),
            new PatchesFile($this->composer, $this->io),
            new DependencyPatches($this->composer, $this->io),
        ];
    }
}
