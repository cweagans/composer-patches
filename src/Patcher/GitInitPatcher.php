<?php

namespace cweagans\Composer\Patcher;

use Composer\IO\IOInterface;
use cweagans\Composer\Patch;

class GitInitPatcher extends GitPatcher
{
    public function apply(Patch $patch, string $path): bool
    {
        // If the target is already a git repo, the standard Git patcher can handle it.
        if (is_dir($path . '/.git')) {
            return false;
        }

        $this->io->write("Creating temporary fake git repo in $path to apply patch", true, IOInterface::VERBOSE);

        // Create a fake Git repo -- just enough to make Git think it's looking at a real repo.
        $dirs = [
            $path . '/.git',
            $path . '/.git/objects',
            $path . '/.git/refs',
        ];
        foreach ($dirs as $dir) {
            mkdir($dir);
        }
        file_put_contents($path . '/.git/HEAD', "ref: refs/heads/main");


        // Use the git patcher to apply the patch.
        $status = parent::apply($patch, $path);

        // Clean up the fake git repo.
        unlink($path . '/.git/HEAD');
        foreach (array_reverse($dirs) as $dir) {
            rmdir($dir);
        }

        return $status;
    }
}
