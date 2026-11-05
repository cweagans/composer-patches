<?php

/**
 * @file
 * Test the RepatchCommand class.
 */

declare(strict_types=1);

namespace cweagans\Composer\Tests\Unit;

use Codeception\Stub;
use Codeception\Test\Unit;
use Composer\Command\InstallCommand;
use Composer\Composer;
use Composer\Config;
use Composer\Console\Application;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Installer\InstallationManager;
use Composer\IO\ConsoleIO;
use Composer\Package\Package;
use Composer\Plugin\PluginManager;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\RepositoryManager;
use Composer\Util\Loop;
use cweagans\Composer\Command\RepatchCommand;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Plugin\Patches;
use InvalidArgumentException;
use React\Promise\PromiseInterface;
use stdClass;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class RepatchCommandTest extends Unit
{
    public function testConfigure(): void
    {
        $command = new RepatchCommand();

        $this->assertSame('patches-repatch', $command->getName());
        $this->assertContains('prp', $command->getAliases());

        $option = $command->getDefinition()->getOption('install-options');
        $this->assertNull($option->getShortcut());
        $this->assertTrue($option->isValueRequired());
        $this->assertTrue($option->isArray());
    }

    public function testExecuteWithoutPluginOrCollection(): void
    {
        $command = new RepatchCommand();
        $app = new Application();
        $app->add(new InstallCommand());
        $app->add($command);

        $composer = new Composer();
        $composer->setConfig(new Config());
        $composer->setEventDispatcher(Stub::makeEmpty(EventDispatcher::class));
        $composer->setPluginManager(Stub::makeEmpty(PluginManager::class, ['getPlugins' => []]));
        $command->setComposer($composer);

        $output = new BufferedOutput();
        $this->assertSame(1, $command->run(new ArrayInput([]), $output));

        $plugin = Stub::makeEmpty(Patches::class, ['getPatchCollection' => null]);
        $composer->setPluginManager(Stub::makeEmpty(PluginManager::class, ['getPlugins' => [$plugin]]));
        $this->assertSame(1, $command->run(new ArrayInput([]), $output));
    }

    /**
     * @dataProvider executeOptionsDataProvider
     * @param list<string> $outerArgv
     * @param array<string, bool|string|list<string>> $expectedOptions
     * @param list<bool> $expectedRunScriptsCalls
     */
    public function testExecute(
        array $outerArgv,
        int $outerVerbosity,
        int $exitCode,
        array $expectedOptions,
        bool $expectedInteractive,
        array $expectedRunScriptsCalls = []
    ): void {
        $harness = $this->createHarness($exitCode);

        /** @var RepatchCommand $command */
        $command = $harness->command;
        /** @var Application $app */
        $app = $harness->app;
        $command->mergeApplicationDefinition();

        $input = new ArgvInput(['composer', 'patches-repatch', ...$outerArgv], $command->getDefinition());
        $output = new BufferedOutput($outerVerbosity);
        $command->setIO(new ConsoleIO($input, $output, $app->getHelperSet()));

        $this->assertSame($exitCode, $command->run($input, $output));
        $this->assertTrue($app->isAutoExitEnabled());
        $this->assertSame(['vendor/patched-package'], $harness->uninstalledPackages);
        $this->assertSame($expectedRunScriptsCalls, $harness->runScriptsCalls);

        $capturedInput = $harness->capturedInput;
        $this->assertInstanceOf(InputInterface::class, $capturedInput);
        $this->assertSame($expectedInteractive, $harness->capturedInteractive);

        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertSame($expectedValue, $capturedInput->getOption($name), "Option --{$name}");
        }
    }

    /**
     * @return iterable<string, array{
     *     outerArgv: list<string>,
     *     outerVerbosity: int,
     *     exitCode: int,
     *     expectedOptions: array<string, bool|string|list<string>>,
     *     expectedInteractive: bool,
     *     expectedRunScriptsCalls?: list<bool>
     * }>
     */
    public static function executeOptionsDataProvider(): iterable
    {
        yield 'default options' => [
            'outerArgv' => [],
            'outerVerbosity' => OutputInterface::VERBOSITY_NORMAL,
            'exitCode' => 0,
            'expectedOptions' => [
                'no-dev' => false,
                'optimize-autoloader' => false,
            ],
            'expectedInteractive' => true,
        ];

        yield 'combined flags, repeated option, and outer verbosity' => [
            'outerArgv' => [
                '--install-options=--no-dev --ignore-platform-req=ext-foo',
                '--install-options=-o --ignore-platform-req=ext-bar',
            ],
            'outerVerbosity' => OutputInterface::VERBOSITY_VERBOSE,
            'exitCode' => 0,
            'expectedOptions' => [
                'no-dev' => true,
                'optimize-autoloader' => true,
                'ignore-platform-req' => ['ext-foo', 'ext-bar'],
                'verbose' => true,
            ],
            'expectedInteractive' => true,
        ];

        yield 'quoted value, non-interactive flag, and exit code propagation' => [
            'outerArgv' => [
                '--install-options=--apcu-autoloader-prefix=\'my_app_\' --no-interaction',
            ],
            'outerVerbosity' => OutputInterface::VERBOSITY_NORMAL,
            'exitCode' => 2,
            'expectedOptions' => [
                'apcu-autoloader-prefix' => 'my_app_',
                'no-interaction' => true,
            ],
            'expectedInteractive' => false,
        ];

        yield 'no-scripts disables event dispatcher scripts' => [
            'outerArgv' => [
                '--install-options=--no-scripts',
            ],
            'outerVerbosity' => OutputInterface::VERBOSITY_NORMAL,
            'exitCode' => 0,
            'expectedOptions' => [
                'no-scripts' => true,
            ],
            'expectedInteractive' => true,
            'expectedRunScriptsCalls' => [false],
        ];
    }

    /**
     * @dataProvider invalidInstallOptionsDataProvider
     * @param class-string<Throwable> $expectedException
     */
    public function testInvalidOptionsFailBeforeUninstallingPackages(
        string $installOptions,
        string $expectedException
    ): void {
        $harness = $this->createHarness(0);

        /** @var RepatchCommand $command */
        $command = $harness->command;
        /** @var Application $app */
        $app = $harness->app;
        $command->mergeApplicationDefinition();

        $input = new ArgvInput(
            ['composer', 'patches-repatch', '--install-options=' . $installOptions],
            $command->getDefinition()
        );
        $output = new BufferedOutput();
        $command->setIO(new ConsoleIO($input, $output, $app->getHelperSet()));

        try {
            $command->run($input, $output);
            $this->fail('Expected exception was not thrown.');
        } catch (Throwable $e) {
            $this->assertInstanceOf($expectedException, $e);
            $this->assertFalse($harness->loadLockedPatchesCalled);
            $this->assertSame([], $harness->uninstalledPackages);
            $this->assertNull($harness->capturedInput);
        }
    }

    /**
     * @return iterable<string, array{string, class-string<Throwable>}>
     */
    public static function invalidInstallOptionsDataProvider(): iterable
    {
        yield 'unknown option' => ['--non-existent-flag', ConsoleRuntimeException::class];
        yield 'package argument' => ['vendor/some-package', InvalidArgumentException::class];
        yield 'disallowed --dry-run' => ['--dry-run', InvalidArgumentException::class];
        yield 'disallowed --download-only' => ['--download-only', InvalidArgumentException::class];
        yield 'disallowed --no-install' => ['--no-install', InvalidArgumentException::class];
        yield 'disallowed --no-plugins' => ['--no-plugins', InvalidArgumentException::class];
        yield 'disallowed --help' => ['--help', InvalidArgumentException::class];
        yield 'disallowed --working-dir' => ['--working-dir=""', InvalidArgumentException::class];
    }

    private function createHarness(int $exitCode): stdClass
    {
        $state = new stdClass();
        $state->loadLockedPatchesCalled = false;
        $state->uninstalledPackages = [];
        $state->runScriptsCalls = [];
        $state->capturedInput = null;
        $state->capturedInteractive = null;

        $composer = new Composer();
        $composer->setConfig(new Config());

        $eventDispatcher = Stub::makeEmpty(EventDispatcher::class, [
            'setRunScripts' => static function (
                bool $runScripts = true
            ) use (
                $state,
                &$eventDispatcher
            ): EventDispatcher {
                $state->runScriptsCalls[] = $runScripts;
                return $eventDispatcher;
            },
        ]);
        $composer->setEventDispatcher($eventDispatcher);

        $patch = new Patch();
        $patch->package = 'vendor/patched-package';
        $patch->description = 'Test patch';
        $patch->url = 'https://example.com/test.patch';

        $patchCollection = new PatchCollection();
        $patchCollection->addPatch($patch);

        $plugin = Stub::makeEmpty(Patches::class, [
            'loadLockedPatches' => static function () use ($state): void {
                $state->loadLockedPatchesCalled = true;
            },
            'getPatchCollection' => $patchCollection,
        ]);
        $composer->setPluginManager(Stub::makeEmpty(PluginManager::class, ['getPlugins' => [$plugin]]));

        $localRepo = new InstalledArrayRepository([
            new Package('vendor/patched-package', '1.0.0.0', '1.0.0'),
            new Package('vendor/unpatched-package', '1.0.0.0', '1.0.0'),
        ]);
        $composer->setRepositoryManager(Stub::makeEmpty(RepositoryManager::class, [
            'getLocalRepository' => $localRepo,
        ]));

        $promise = Stub::makeEmpty(PromiseInterface::class);
        $composer->setInstallationManager(Stub::makeEmpty(InstallationManager::class, [
            'uninstall' => static function ($repo, UninstallOperation $op) use ($state, $promise): PromiseInterface {
                $state->uninstalledPackages[] = $op->getPackage()->getName();
                return $promise;
            },
        ]));
        $composer->setLoop(Stub::makeEmpty(Loop::class));

        $spyInstallCommand = new class ($exitCode, $state) extends InstallCommand {
            private int $exitCode;
            private stdClass $state;

            public function __construct(int $exitCode, stdClass $state)
            {
                $this->exitCode = $exitCode;
                $this->state = $state;
                parent::__construct();
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $this->state->capturedInput = $input;
                $this->state->capturedInteractive = $this->getIO()->isInteractive();
                return $this->exitCode;
            }
        };

        $command = new RepatchCommand();
        $app = new Application();
        $app->add($spyInstallCommand);
        $app->add($command);

        $command->setComposer($composer);

        $state->command = $command;
        $state->app = $app;

        return $state;
    }
}
