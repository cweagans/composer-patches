<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;

class BsdPatchPatcher extends PatcherBase
{
    protected string $tool = 'patch';

    public function apply(Patch $patch): void
    {
        return;
    }

    public function canUse(): bool
    {
        $executor = new ProcessExecutor($this->io);
        $output = "";
        $result = $executor->execute($this->patchTool() . ' --version', $output);
        // TODO: Is it a valid assumption to assume that if GNU is *not* in the version output, that it's BSD patch?
        return ($result === 0) && (!str_contains($output, 'GNU patch'));
    }
}
