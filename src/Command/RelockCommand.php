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
        $file_name = pathinfo(Patches::getPatchesLockFilePath(), \PATHINFO_BASENAME);
        $this
            ->setName('patches-relock')
            ->setDescription("Find all patches defined in the project and re-write $file_name.")
            ->setAliases(['prl']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin = $this->getPatchesPluginInstance();
        if (is_null($plugin)) {
            return 1;
        }

        $file_path = $plugin->getLockFile()->getPath();
        $file_name = pathinfo($file_path, \PATHINFO_BASENAME);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $plugin->createNewPatchesLock();
        $output->write("  - <info>$file_name</info> has been recreated successfully.", true);
        return 0;
    }
}
