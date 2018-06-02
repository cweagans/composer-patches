<?php

namespace cweagans\Composer\Capability;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\Capability;
use Composer\Plugin\PluginInterface;

abstract class BaseResolverProvider implements Capability, ResolverProvider
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * BaseResolverProvider constructor.
     *
     * Stores values passed by the plugin manager for later use.
     *
     * @param array $args
     *   An array of args passed by the plugin manager.
     */
    public function __construct($args)
    {
        $this->composer = $args['composer'];
        $this->io = $args['io'];
        $this->plugin = $args['plugin'];
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getResolvers();
}
