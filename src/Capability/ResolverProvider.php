<?php

namespace cweagans\Composer\Capability;

use Composer\Plugin\Capability\Capability;
use cweagans\Composer\Resolvers\ResolverBase;

/**
 * Resolver provider interface.
 *
 * This capability will receive an array with 'composer' and 'io' keys as
 * constructor arguments. It also contains a 'plugin' key containing the
 * plugin instance that declared the capability.
 */
interface ResolverProvider extends Capability
{
    /**
     * Retrieves an array of PatchResolvers.
     *
     * @return ResolverBase[]
     */
    public function getResolvers();
}
