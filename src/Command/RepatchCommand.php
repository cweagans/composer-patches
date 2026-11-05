<?php

declare(strict_types=1);

namespace cweagans\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\DependencyResolver\Operation\UninstallOperation;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class RepatchCommand extends PatchesCommandBase
{
    private const DISALLOWED_INSTALL_OPTIONS = [
        'dry-run',
        'download-only',
        'no-install',
        'no-plugins',
        'help',
        'version',
        'working-dir',
        'no-cache',
    ];

    protected function configure(): void
    {
        $this->setName('patches-repatch');
        $this->setDescription('Delete, re-download, and re-patch each dependency with any patches defined.');
        $this->addOption(
            'install-options',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Options to pass to composer install (use --install-options="...").'
        );
        $this->setAliases(['prp']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $installCommand = $this->getApplication()->find('install');
        $installInput = $this->createInstallInput($input, $output, $installCommand);

        $plugin = $this->getPatchesPluginInstance();
        if (is_null($plugin)) {
            return 1;
        }

        $plugin->loadLockedPatches();
        $patchCollection = $plugin->getPatchCollection();
        if (is_null($patchCollection)) {
            return 1;
        }

        $composer = $this->requireComposer();
        $input->setInteractive($installInput->isInteractive());

        $localRepository = $composer
            ->getRepositoryManager()
            ->getLocalRepository();

        $patched_packages = $patchCollection->getPatchedPackages();
        $packages = array_filter($localRepository->getPackages(), function ($val) use ($patched_packages) {
            return in_array($val->getName(), $patched_packages);
        });

        // Remove patched packages so that we can re-install/re-patch.
        $promises = [];
        foreach ($packages as $package) {
            $uninstallOperation = new UninstallOperation($package);
            $promises[] = $composer
                ->getInstallationManager()
                ->uninstall($localRepository, $uninstallOperation);
        }
        // Wait for uninstalls to finish.
        $promises = array_filter($promises);
        if (!empty($promises)) {
            $composer->getLoop()->wait($promises);
        }

        if ($installCommand instanceof BaseCommand) {
            $installCommand->setComposer($composer);
            $installCommand->setIO($this->getIO());
        }

        if ($installInput->hasOption('no-scripts') && $installInput->getOption('no-scripts')) {
            $composer->getEventDispatcher()->setRunScripts(false);
        }

        return $installCommand->run($installInput, $output);
    }

    private function createInstallInput(
        InputInterface $outerInput,
        OutputInterface $output,
        Command $installCommand
    ): StringInput {
        $installCommand->mergeApplicationDefinition();
        $definition = $installCommand->getDefinition();

        $rawOptions = trim(implode(' ', (array) $outerInput->getOption('install-options')));
        $prefix = $definition->hasArgument('command') ? 'install' : '';

        $installInput = new StringInput(trim($prefix . ' ' . $rawOptions));
        $installInput->bind($definition);
        $installInput->validate();

        if ($installInput->hasArgument('packages') && count((array) $installInput->getArgument('packages')) > 0) {
            throw new InvalidArgumentException('Do not pass package arguments via --install-options.');
        }

        foreach (self::DISALLOWED_INSTALL_OPTIONS as $option) {
            if (
                $installInput->hasOption($option)
                && $installInput->getOption($option) !== false
                && $installInput->getOption($option) !== null
            ) {
                throw new InvalidArgumentException(
                    sprintf('Do not pass "--%s" via --install-options.', $option)
                );
            }
        }

        if (
            $output->isVerbose()
            && $definition->hasOption('verbose')
            && !$installInput->getOption('verbose')
            && (!$installInput->hasOption('quiet') || !$installInput->getOption('quiet'))
        ) {
            $installInput = new StringInput(trim($prefix . ' --verbose ' . $rawOptions));
            $installInput->bind($definition);
        }

        $isQuiet = $installInput->hasOption('quiet') && (bool) $installInput->getOption('quiet');
        $isNoInteraction = $installInput->hasOption('no-interaction')
            && (bool) $installInput->getOption('no-interaction');
        $installInput->setInteractive($outerInput->isInteractive() && !$isQuiet && !$isNoInteraction);

        return $installInput;
    }
}
