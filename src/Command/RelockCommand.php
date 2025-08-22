<?php

declare(strict_types=1);

namespace cweagans\Composer\Command;

use cweagans\Composer\Plugin\Patches;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RelockCommand extends PatchesCommandBase
{
    protected function configure(): void
    {
        $this->setName('patches-relock');
        $this->setDescription("Find all patches defined in the project and re-write the patches lock file.");
        $this->setAliases(['prl']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin = $this->getPatchesPluginInstance();
        if (is_null($plugin)) {
            return 1;
        }

        if (file_exists($plugin->getLockFile()->getPath())) {
            unlink($plugin->getLockFile()->getPath());
        }
        $plugin->createNewPatchesLock();
        $filename = pathinfo($plugin->getPatchesLockFilePath(), \PATHINFO_BASENAME);
        $output->write("  - <info>$filename</info> has been recreated successfully.", true);
        return 0;
    }
}
