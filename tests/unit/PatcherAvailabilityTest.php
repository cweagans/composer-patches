<?php

/**
 * @file
 * Test the Patchers classes.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\IO\NullIO;
use cweagans\Composer\Patcher\BsdPatchPatcher;
use cweagans\Composer\Patcher\GnuPatchPatcher;
use cweagans\Composer\Patcher\GitPatcher;

class PatcherAvailabilityTest extends Unit
{
    /**
     * Test missing/broken tool behavior (+ some happy paths)
     *
     * @dataProvider missingOrBrokenToolBehaviorsDataProvider
     */
    public function testMissingOrBrokenToolBehaviors($patcher, $expected)
    {
        $this->assertEquals($patcher->canUse(), $expected);
    }

    public function missingOrBrokenToolBehaviorsDataProvider()
    {
        $patcher = new GitPatcher(new Composer(), new NullIO());
        $patcher->toolPathOverride = codecept_data_dir('testtools/intentionally-missing-executable');
        yield 'missing git' => [$patcher, false];

        $patcher = new GitPatcher(new Composer(), new NullIO());
        $patcher->toolPathOverride = codecept_data_dir('testtools/broken-git.sh');
        yield 'broken git' => [$patcher, false];

        $patcher = new GitPatcher(new Composer(), new NullIO());
        $patcher->toolPathOverride = codecept_data_dir('testtools/git.sh');
        yield 'working git' => [$patcher, true];

        $patcher = new GitPatcher(new Composer(), new NullIO());
        yield 'working (real) git' => [$patcher, true];
    }
}
