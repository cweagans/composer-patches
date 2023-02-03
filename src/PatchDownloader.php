<?php

namespace cweagans\Composer;

use Composer\Composer;
use cweagans\Composer\Downloader\DownloaderInterface;
use Composer\IO\IOInterface;
use cweagans\Composer\Capability\Downloader\DownloaderProvider;
use UnexpectedValueException;

class PatchDownloader
{
    protected Composer $composer;

    protected IOInterface $io;

    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Download a patch using the available downloaders.
     *
     * A downloader will update Patch->localPath if it was able to download the patch.
     *
     * @param Patch $patch
     *   The patch to download.
     */
    public function downloadPatch(Patch $patch)
    {
        foreach ($this->getDownloaders() as $downloader) {
            $downloader->download($patch);

            if (isset($patch->localPath)) {
                return;
            }
        }
    }

    /**
     * Gather a list of all patch downloaders from all enabled Composer plugins.
     *
     * @return DownloaderInterface[]
     *   A list of Downloaders that are available.
     */
    protected function getDownloaders(): array
    {
        static $downloaders;

        if (!is_null($downloaders)) {
            return $downloaders;
        }

        $downloaders = [];
        $plugin_manager = $this->composer->getPluginManager();
        $capabilities = $plugin_manager->getPluginCapabilities(
            DownloaderProvider::class,
            ['composer' => $this->composer, 'io' => $this->io]
        );
        foreach ($capabilities as $capability) {
            /** @var DownloaderProvider $capability */
            $newDownloaders = $capability->getDownloaders();
            foreach ($newDownloaders as $downloader) {
                if (!$downloader instanceof DownloaderInterface) {
                    throw new UnexpectedValueException(
                        'Plugin capability ' . get_class($capability) . ' returned an invalid value.'
                    );
                }
            }
            $downloaders = array_merge($downloaders, $newDownloaders);
        }

        return $downloaders;
    }
}
