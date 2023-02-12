---
title: Events
weight: 30
---

In the process of applying patches, Composer Patches emits several different events. The event dispatcher system is the one provided by Composer, so any documentation related to the Composer event dispatcher also applies to plugins extending Composer Patches.

## Subscribing to an event.

This functionality comes directly from Composer. In your plugin class, implement `\Composer\EventDispatcher\EventSubscriberInterface`. Your `getSubscribedEvents()` method should return a list of events that you want to subscribe to and their respective handlers.

{{< highlight php "hl_lines=5 13-14" >}}

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Event\PatchEvents;

class YourPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public function getSubscribedEvents(): array
    {
        return array(
            PatchEvents::PRE_PATCH_GUESS_DEPTH => ['yourHandlerHere'],
            PatchEvents::PRE_PATCH_APPLY => ['aDifferentHandler', 10],
        );
    }
    
    [...]
}
{{< /highlight >}}

A full list of events can be found in `\cweagans\Composer\Event\PatchEvents` and `\cweagans\Composer\Event\PluginEvents`.

## Writing the event handler

Once you've subscribed to an event, you need to write the handler. The handler function should be located in your main plugin class and be a `public` method. If the event is listed in `\cweagans\Composer\Event\PatchEvents`, your handler function will receive a `\cweagans\Composer\Event\PatchEvent` as the first (and only) argument. If the event is listed in `\cweagans\Composer\Event\PluginEvents`, your handler function will receive a `\cweagans\Composer\Event\PluginEvent` object as the first (and only) argument.
