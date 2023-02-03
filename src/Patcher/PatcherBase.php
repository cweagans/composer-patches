<?php

namespace cweagans\Composer\Patcher;

use Composer\Composer;
use Composer\IO\IOInterface;
use cweagans\Composer\Patch;

abstract class PatcherBase implements PatcherInterface
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
     * If set, the Patcher object will use this path instead of a $PATH lookup to execute the appropriate tool.
     *
     * @var string
     */
    public string $toolPathOverride;

    /**
     * The tool executable that the Patcher object should use (for internal use).
     *
     * @var string
     */
    protected string $tool;

    /**
     * {@inheritDoc}
     */
    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Return the tool to run when applying patches (when applicable).
     *
     * @return string
     */
    protected function patchTool(): string
    {
        if (isset($this->toolPathOverride) && !empty($this->toolPathOverride)) {
            return $this->toolPathOverride;
        }


        return $this->tool;
    }

    /**
     * @inheritDoc
     */
    abstract public function apply(Patch $patch): bool;

    /**
     * @inheritDoc
     */
    abstract public function canUse(): bool;
}
