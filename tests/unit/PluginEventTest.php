<?php

/**
 * @file
 * Tests event dispatching.
 */

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
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
        $plugin_event = new PluginEvent($event_name, $capabilities);
        $this->assertEquals($event_name, $plugin_event->getName());
        $this->assertEquals($capabilities, $plugin_event->getCapabilities());

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
