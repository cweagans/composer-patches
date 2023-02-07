<?php

namespace cweagans\Composer\Downloader;

use Composer\Composer;
use Composer\IO\IOInterface;
use cweagans\Composer\Downloader\Exception\HashMismatchException;
use cweagans\Composer\Patch;

abstract class DownloaderBase implements DownloaderInterface
{
    /**
     * The main Composer object.
     *
     * @var Composer
     */
    protected Composer $composer;

    /**
     * An array of operations that will be executed during this composer execution.
     *
     * @var IOInterface
     */
    protected IOInterface $io;

    /**
     * @inheritDoc
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * @inheritDoc
     * @throws HashMismatchException
     */
    abstract public function download(Patch $patch): void;
}
