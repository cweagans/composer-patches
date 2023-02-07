<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Stub;
use Codeception\Test\Unit;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\CommandProvider;
use cweagans\Composer\Capability\Downloader\CoreDownloaderProvider;
use cweagans\Composer\Downloader\ComposerDownloader;

class CommandProviderTest extends Unit
{
    public function testGetCommands()
    {
        $composer = new Composer();
        $composer->setConfig(new Config());

        $commandProvider = new CommandProvider();

        $commands = $commandProvider->getCommands();
        $this->assertCount(2, $commands);
        $this->assertInstanceOf(BaseCommand::class, $commands[0]);
        $this->assertInstanceOf(BaseCommand::class, $commands[1]);
    }
}
