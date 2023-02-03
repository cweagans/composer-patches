<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;

class GitPatcher extends PatcherBase
{
    protected string $tool = 'git';

    public function apply(Patch $patch): bool
    {
        return false;
    }

    public function canUse(): bool
    {
        $executor = new ProcessExecutor($this->io);
        $output = "";
        $result = $executor->execute($this->patchTool() . ' --version', $output);
        return ($result === 0);
    }
}
