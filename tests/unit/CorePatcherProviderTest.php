<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Composer\Composer;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Patcher\CorePatcherProvider;
use cweagans\Composer\Patcher\BsdPatchPatcher;
use cweagans\Composer\Patcher\GitPatcher;
use cweagans\Composer\Patcher\GnuGPatchPatcher;
use cweagans\Composer\Patcher\GnuPatchPatcher;

class CorePatcherProviderTest extends Unit
{
    public function testGetPatchers()
    {
        $patcherProvider = new CorePatcherProvider([
            'composer' => new Composer(),
            'io' => new NullIO(),
            'plugin' => Stub::makeEmpty(PluginInterface::class),
        ]);

        $patchers = $patcherProvider->getPatchers();

        $this->assertCount(4, $patchers);
        $this->assertInstanceOf(GitPatcher::class, $patchers[0]);
        $this->assertInstanceOf(GnuPatchPatcher::class, $patchers[1]);
        $this->assertInstanceOf(GnuGPatchPatcher::class, $patchers[2]);
        $this->assertInstanceOf(BsdPatchPatcher::class, $patchers[3]);
    }
}
