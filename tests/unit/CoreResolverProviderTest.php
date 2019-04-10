<?php

namespace cweagans\Composer\Tests;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use cweagans\Composer\Capability\CoreResolverProvider;

class CoreResolverProviderTest extends Unit
{
    public function testGetResolvers()
    {
        $resolverProvider = new CoreResolverProvider([
            'composer' => Stub::make(\Composer\Composer::class),
            'io' => new \Composer\IO\NullIO(),
            'plugin' => Stub::makeEmpty(\Composer\Plugin\PluginInterface::class),
        ]);

        $resolvers = $resolverProvider->getResolvers();

        $this->assertCount(2, $resolvers);
        $this->assertInstanceOf(\cweagans\Composer\Resolvers\RootComposer::class, $resolvers[0]);
        $this->assertInstanceOf(\cweagans\Composer\Resolvers\PatchesFile::class, $resolvers[1]);
    }
}
