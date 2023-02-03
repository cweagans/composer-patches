<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;

class GnuPatchPatcher extends PatcherBase
{
    protected string $tool = 'patch';

    public function apply(Patch $patch): bool
    {
        return false;
    }

    public function canUse(): bool
    {
        $executor = new ProcessExecutor($this->io);
        $output = "";
        $result = $executor->execute($this->patchTool() . ' --version', $output);
        return ($result === 0) && (str_contains($output, 'GNU patch'));
    }
}
