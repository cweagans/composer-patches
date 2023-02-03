<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;
use cweagans\Composer\Patcher\Exception\ToolNotAvailableException;

class GitPatcher extends PatcherBase
{
    protected string $tool = 'git';

    public function apply(Patch $patch): void
    {
        if (!$this->checkGit()) {
            throw new ToolNotAvailableException('git');
        }

        return;
    }

    protected function checkGit(): bool
    {
        static $gitAvailable;

        if (!is_null($gitAvailable)) {
            return $gitAvailable;
        }

        $executor = new ProcessExecutor($this->io);
        $result = $executor->execute($this->patchTool());
        $gitAvailable = ($result === 0);
        return $gitAvailable;
    }
}
