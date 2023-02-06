<?php

namespace cweagans\Composer\Tests\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Module;
use Symfony\Component\Filesystem\Filesystem;

class Acceptance extends Module
{
    public function _afterSuite()
    {
        $filesystem = new Filesystem();

        $composerLockFiles = glob($this->_getFixturesDir() . '/*/composer.lock');
        $filesystem->remove($composerLockFiles);

        $patchesLockFiles = glob($this->_getFixturesDir() . '/*/patches.lock');
        $filesystem->remove($patchesLockFiles);

        $composerVendorDirs = glob($this->_getFixturesDir() . '/*/vendor');
        $filesystem->remove($composerVendorDirs);
    }

    protected function _getFixturesDir()
    {
        return codecept_data_dir('fixtures');
    }

}
