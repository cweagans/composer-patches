<?php

namespace cweagans\Composer\Patcher;

use Composer\IO\IOInterface;
use cweagans\Composer\Patch;

class GnuPatchPatcher extends PatcherBase
{
    protected string $tool = 'patch';

    public function apply(Patch $patch, string $path): bool
    {
        $status = $this->executeCommand(
            '%s -p%s --dry-run --no-backup-if-mismatch -d %s -i %s',
            $this->patchTool(),
            $patch->depth,
            $path,
            $patch->localPath
        );
        if (!$status) {
            return false;
        }

        return $this->executeCommand(
            '%s -p%s --no-backup-if-mismatch -d %s -i %s',
            $this->patchTool(),
            $patch->depth,
            $path,
            $patch->localPath
        );
    }

    public function canUse(): bool
    {
        $output = "";
        $result = $this->executor->execute($this->patchTool() . ' --version', $output);
        $usable = ($result === 0) && (str_contains($output, 'GNU patch'));

        $this->io->write(self::class . " usable: " . ($usable ? "yes" : "no"), true, IOInterface::DEBUG);

        return $usable;
    }
}
