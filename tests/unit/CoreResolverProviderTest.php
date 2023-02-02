<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\CoreResolverProvider;
use cweagans\Composer\Resolvers\PatchesFile;
use cweagans\Composer\Resolvers\RootComposer;

class CoreResolverProviderTest extends Unit
{
    public function testGetResolvers()
    {
        $resolverProvider = new CoreResolverProvider([
            'composer' => Stub::make(Composer::class),
            'io' => new NullIO(),
            'plugin' => Stub::makeEmpty(PluginInterface::class),
        ]);

        $resolvers = $resolverProvider->getResolvers();

        $this->assertCount(2, $resolvers);
        $this->assertInstanceOf(RootComposer::class, $resolvers[0]);
        $this->assertInstanceOf(PatchesFile::class, $resolvers[1]);
    }
}
