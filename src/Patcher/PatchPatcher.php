<?php

namespace cweagans\Composer\Patcher;

use cweagans\Composer\Patch;
use Composer\IO\IOInterface;

class PatchPatcher extends PatcherBase
{
  protected string $tool = 'patch';

  public function apply(Patch $patch, string $path): bool
  {
    // Dry run first.
    $status = $this->executeCommand(
      "sh -lc '%s -p%s --dry-run --no-backup-if-mismatch -d %s < %s'",
      $this->patchTool(),
      $patch->depth,
      $path,
      $patch->localPath
    );

    // If the check failed, then don't proceed with actually applying the patch.
    if (!$status) {
      return false;
    }

    // Otherwise, we can try to apply the patch.
    return $this->executeCommand(
      "sh -lc '%s -p%s --no-backup-if-mismatch -d %s < %s'",
      $this->patchTool(),
      $patch->depth,
      $path,
      $patch->localPath
    );
  }

  public function canUse(): bool
  {
    $output = '';
    $result = $this->executor->execute($this->patchTool() . ' --version', $output);
    return ($result === 0);
  }
}
