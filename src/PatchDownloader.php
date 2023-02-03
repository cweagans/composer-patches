<?php

namespace cweagans\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;

class PatchDownloader
{
    protected Composer $composer;

    protected IOInterface $io;

    protected PatchCollection $patchCollection;

    public function __construct(Composer $composer, IOInterface $io, PatchCollection $patchCollection)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->patchCollection = $patchCollection;
    }

    public function downloadPatches()
    {
    }

    /**
     * Gather a list of all patch downloaders from all enabled Composer plugins.
     *
     * @return DownloaderInterface[]
     *   A list of Downloaders that are available.
     */
    protected function getDownloaders(): array
    {
        $downloaders = [];
        $plugin_manager = $this->composer->getPluginManager();
        $capabilities = $plugin_manager->getPluginCapabilities(
            Downloader,
            ['composer' => $this->composer, 'io' => $this->io]
        );
        foreach ($capabilities as $capability) {
            /** @var ResolverProvider $capability */
            $newResolvers = $capability->getResolvers();
            foreach ($newResolvers as $resolver) {
                if (!$resolver instanceof ResolverBase) {
                    throw new UnexpectedValueException(
                        'Plugin capability ' . get_class($capability) . ' returned an invalid value.'
                    );
                }
            }
            $resolvers = array_merge($resolvers, $newResolvers);
        }

        return $resolvers;
    }
}
