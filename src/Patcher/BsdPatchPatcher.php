<?php

namespace cweagans\Composer\Patcher;

use Composer\Util\ProcessExecutor;
use cweagans\Composer\Patch;
use cweagans\Composer\Patcher\Exception\ToolNotAvailableException;

class BsdPatchPatcher extends PatcherBase
{
    protected string $tool = 'patch';

    public function apply(Patch $patch): void
    {
        if (!$this->checkBsdPatch()) {
            throw new ToolNotAvailableException('patch (BSD)');
        }

        return;
    }

    protected function checkBsdPatch(): bool
    {
        static $bsdPatchAvailable;

        if (!is_null($bsdPatchAvailable)) {
            return $bsdPatchAvailable;
        }

        $executor = new ProcessExecutor($this->io);
        $output = "";
        $result = $executor->execute($this->patchTool() . ' --version', $output);
        // TODO: Is it a valid assumption to assume that if GNU is *not* in the version output, that it's BSD patch?
        $bsdPatchAvailable = ($result === 0) && (!str_contains($output, 'GNU patch'));
        return $bsdPatchAvailable;
    }
}
