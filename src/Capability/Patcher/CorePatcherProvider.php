<?php

namespace cweagans\Composer\Capability\Patcher;

use cweagans\Composer\Patcher\BsdPatchPatcher;
use cweagans\Composer\Patcher\GitPatcher;
use cweagans\Composer\Patcher\GnuGPatchPatcher;
use cweagans\Composer\Patcher\GnuPatchPatcher;

class CorePatcherProvider extends BasePatcherProvider
{
    /**
     * @inheritDoc
     */
    public function getPatchers(): array
    {
        return [
            new GitPatcher($this->composer, $this->io),
            new GnuPatchPatcher($this->composer, $this->io),
            new GnuGPatchPatcher($this->composer, $this->io),
            new BsdPatchPatcher($this->composer, $this->io),
        ];
    }
}
