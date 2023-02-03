<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;
use cweagans\Composer\Patcher\Exception\ToolNotAvailableException;

class GnuPatchPatcher extends PatcherBase
{
    protected string $tool = 'patch';

    public function apply(Patch $patch): void
    {
        if (!$this->checkGnuPatch()) {
            throw new ToolNotAvailableException('patch (GNU)');
        }

        return;
    }

    protected function checkGnuPatch(): bool
    {
        static $gnuPatchAvailable;

        if (!is_null($gnuPatchAvailable)) {
            return $gnuPatchAvailable;
        }

        $executor = new ProcessExecutor($this->io);
        $output = "";
        $result = $executor->execute($this->patchTool() . ' --version', $output);
        $gnuPatchAvailable = ($result === 0) && (str_contains($output, 'GNU patch'));
        return $gnuPatchAvailable;
    }
}
