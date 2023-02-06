<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;
use Composer\IO\IOInterface;

class BsdPatchPatcher extends PatcherBase
{
    protected string $tool = 'patch';

    public function apply(Patch $patch, string $path): bool
    {
        // TODO: Dry run first?

        return $this->executeCommand(
            '%s -p%s --posix --batch -d %s -i %s',
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
        // TODO: Is it a valid assumption to assume that if GNU is *not* in the version output, that it's BSD patch?
        $usable = ($result === 0) && (!str_contains($output, 'GNU patch'));

        $this->io->write(self::class . " usable: " . ($usable ? "yes" : "no"), true, IOInterface::DEBUG);

        return $usable;
    }
}
