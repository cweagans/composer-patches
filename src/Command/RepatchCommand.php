<?php

declare(strict_types=1);

namespace cweagans\Composer\Command;

use Composer\DependencyResolver\Operation\UninstallOperation;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RepatchCommand extends PatchesCommandBase
{
    protected function configure(): void
    {
        $this->setName('patches-repatch');
        $this->setDescription('Delete, re-download, and re-patch each dependency with any patches defined.');
        $this->addOption(
            'install-options',
            'o',
            InputOption::VALUE_REQUIRED,
            'Allows you to set the options for the composer install.'
        );
        $this->setAliases(['prp']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin = $this->getPatchesPluginInstance();
        if (is_null($plugin)) {
            return 1;
        }

        $plugin->loadLockedPatches();
        $patchCollection = $plugin->getPatchCollection();
        if (is_null($patchCollection)) {
            return 1;
        }

        $localRepository = $this->requireComposer()
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
            $promises[] = $this->requireComposer()
                ->getInstallationManager()
                ->uninstall($localRepository, $uninstallOperation);
        }
        // Wait for uninstalls to finish.
        $promises = array_filter($promises);
        if (!empty($promises)) {
            $this->requireComposer()->getLoop()->wait($promises);
        }

        $parameters = ['command' => 'install'];

        if (!empty($input->getOption('install-options'))) {
            $installOptions = explode(' ', $input->getOption('install-options'));

            foreach ($installOptions as $installOption) {
                $parameters[$installOption] = true;
            }
        }

        $input = new ArrayInput($parameters);
        $application = $this->getApplication();
        $application->setAutoExit(false);
        $application->run($input, $output);

        return 0;
    }
}
