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
        $this->assertNotEmpty($commands);
        foreach ($commands as $command) {
            $this->assertInstanceOf(BaseCommand::class, $command);
        }
    }
}
