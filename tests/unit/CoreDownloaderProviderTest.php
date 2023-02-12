<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Stub;
use Codeception\Test\Unit;
use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Downloader\CoreDownloaderProvider;
use cweagans\Composer\Downloader\ComposerDownloader;
use cweagans\Composer\Downloader\DownloaderInterface;

class CoreDownloaderProviderTest extends Unit
{
    public function testGetDownloaders()
    {
        $composer = new Composer();
        $composer->setConfig(new Config());

        $downloaderProvider = new CoreDownloaderProvider([
            'composer' => $composer,
            'io' => new NullIO(),
            'plugin' => Stub::makeEmpty(PluginInterface::class),
        ]);

        $downloaders = $downloaderProvider->getDownloaders();

        $this->assertCount(1, $downloaders);
        $this->assertContainsOnlyInstancesOf(DownloaderInterface::class, $downloaders);
    }
}
