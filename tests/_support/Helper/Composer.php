<?php

namespace cweagans\Composer\Tests\Helper;

use Codeception\Module;
use Codeception\PHPUnit\TestCase;
use Codeception\TestInterface;
use Composer\Console\Application;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Composer extends Module
{
    public string $output;
    public int $status;

    public function _before(TestInterface $test): void
    {
        $this->output = '';
    }

    /**
     * Run a Composer command and capture the output and status code.
     *
     * @param string $command
     *   The Composer subcommand to run (install, update, etc).
     * @param array $args
     *   Any additional arguments to pass to the command.
     * @param $failIfNonZero
     *   If true, immediately fail if the result is nonzero. Defaults to true.
     *
     * ```php
     * <?php
     * $I->runComposerCommand('install');
     *
     * // run in very verbose mode.
     * $I->runComposerCommand('install', ['-vvv']);
     *
     * // do not fail if composer install fails.
     * $I->runComposerCommand('install', [], false);
     * ```
     */
    public function runComposerCommand(string $command, array $args, bool $failIfNonZero = true): void
    {
        $input = new ArrayInput(['command' => $command, ...$args]);
        $output = new BufferedOutput();
        $application = new Application();
        $application->setAutoExit(false);

        $this->status = $application->run($input, $output);
        $this->output = $output->fetch();

        if ($failIfNonZero && $this->status !== 0) {
            Assert::fail("'composer {$command}' result code was {$this->status}");
        }
    }

    /**
     * Check that the last Composer command output includes text.
     */
    public function seeInComposerOutput(string $text): void
    {
        TestCase::assertStringContainsString($text, $this->output);
    }

    /**
     * Check that the last Composer command output does not include text.
     */
    public function dontSeeInComposerOutput(string $text): void
    {
        TestCase::assertStringNotContainsString($text, $this->output);
    }

    /**
     * Return the output from the last composer command.
     */
    public function grabComposerOutput(): string
    {
        return $this->output;
    }

    /**
     * Check result code of last Composer command is equal to $code.
     */
    public function seeComposerStatusCodeIs(int $code): void
    {
        $this->assertEquals($this->status, $code, "status code is {$code}");
    }

    /**
     * Check result code of last Composer command is not equal to $code.
     */
    public function seeComposerStatusCodeIsNot(int $code): void
    {
        $this->assertNotEquals($this->status, $code, "status code is not {$code}");
    }

}
