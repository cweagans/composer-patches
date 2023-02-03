<?php

/**
 * @file
 * Dispatch events when patches are applied.
 */

namespace cweagans\Composer;

class PatchEvents
{
    /**
     * The PRE_PATCH_DOWNLOAD event occurs before a patch is downloaded
     *
     * The event listener method receives a cweagans\Composer\PatchEvent instance.
     *
     * @var string
     */
    public const PRE_PATCH_DOWNLOAD = 'pre-patch-download';

    /**
     * The POST_PATCH_DOWNLOAD event occurs after a patch is downloaded
     *
     * The event listener method receives a cweagans\Composer\PatchEvent instance.
     *
     * @var string
     */
    public const POST_PATCH_DOWNLOAD = 'post-patch-download';

    /**
     * The PRE_PATCH_APPLY event occurs before a patch is applied.
     *
     * The event listener method receives a cweagans\Composer\PatchEvent instance.
     *
     * @var string
     */
    public const PRE_PATCH_APPLY = 'pre-patch-apply';

    /**
     * The POST_PATCH_APPLY event occurs after a patch is applied.
     *
     * The event listener method receives a cweagans\Composer\PatchEvent instance.
     *
     * @var string
     */
    public const POST_PATCH_APPLY = 'post-patch-apply';
}
