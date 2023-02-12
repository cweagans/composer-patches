<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\IO\NullIO;
use cweagans\Composer\Event\PluginEvent;
use cweagans\Composer\Event\PluginEvents;

class PluginEventTest extends Unit
{
    /**
     * Tests all the getters.
     *
     * @dataProvider pluginEventDataProvider
     */
    public function testGetters($event_name, array $capabilities)
    {
        $composer = new Composer();
        $io = new NullIO();

        $plugin_event = new PluginEvent($event_name, $capabilities, $composer, $io);
        $this->assertEquals($event_name, $plugin_event->getName());
        $this->assertEquals($capabilities, $plugin_event->getCapabilities());
        $this->assertEquals($composer, $plugin_event->getComposer());
        $this->assertEquals($io, $plugin_event->getIO());

        $plugin_event->setCapabilities(['something']);
        $this->assertEquals(['something'], $plugin_event->getCapabilities());
    }

    public function pluginEventDataProvider()
    {
        return array(
            array(PluginEvents::POST_DISCOVER_DOWNLOADERS, []),
            array(PluginEvents::POST_DISCOVER_PATCHERS, []),
            array(PluginEvents::POST_DISCOVER_RESOLVERS, []),
        );
    }
}
