<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Config;
use cweagans\Composer\Capability\CommandProvider;

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
