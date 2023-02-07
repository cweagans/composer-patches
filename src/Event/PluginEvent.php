<?php

namespace cweagans\Composer\Event;

use Composer\EventDispatcher\Event;

class PluginEvent extends Event
{
    /**
     * @var array $capabilities
     */
    protected array $capabilities;

    /**
     * Constructs a PluginEvent object.
     *
     * @param string $eventName
     * @param array $capabilities
     */
    public function __construct(string $eventName, array $capabilities)
    {
        parent::__construct($eventName);
        $this->capabilities = $capabilities;
    }

    /**
     * Get the list of capabilities that were discovered.
     *
     * @return array
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Replace the list of capabilities that were discovered.
     *
     * You should take care to only include the correct type of capability classes here. e.g. If you're responding to
     * the POST_DISCOVER_DOWNLOADERS event, you should only include implementations of DownloaderInterface.
     *
     * @param array $capabilities
     *   A complete list of capability objects.
     */
    public function setCapabilities(array $capabilities): void
    {
        $this->capabilities = $capabilities;
    }
}
