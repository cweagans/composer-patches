---
title: Capabilities
weight: 20
---

Your plugin can be capable of providing a `Resolver`, a `Downloader`, and/or a `Patcher`.

## Enabling a plugin capability

This functionality comes directly from Composer. In your plugin class, implement `\Composer\Plugin\Capable`. Your `getCapabilities()` method should return a list of capabilities that your plugin offers.

{{< highlight php "hl_lines=7 15-17" >}}

use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use cweagans\Composer\Capability\Downloader\DownloaderProvider;
use cweagans\Composer\Capability\Patcher\PatcherProvider;
use cweagans\Composer\Capability\Resolver\ResolverProvider;

class YourPlugin implements PluginInterface, Capable
{
    /**
     * @inheritDoc
     */
    public function getCapabilities(): array
    {
        return [
            ResolverProvider::class => YourResolverProvider::class,
            DownloaderProvider::class => YourDownloaderProvider::class,
            PatcherProvider::class => YourPatcherProvider::class,
        ];
    }
    
    [...]
}
{{< /highlight >}}


## Providers

Next, you need to implement the provider for your capability. The provider is responsible for instantiating any capability classes that your plugin offers and returns a list of them. Rather than duplicating the code here, you should refer to `\cweagans\Composer\Capability\Downloader\CoreDownloaderProvider`, `\cweagans\Composer\Capability\Patcher\CorePatcherProvider`, and `\cweagans\Composer\Capability\Resolver\CoreResolverProvider` for examples of how to write these classes. Your provider should extend one of the base classes provided (`\cweagans\Composer\Capability\Downloader\BaseDownloaderProvider`, `\cweagans\Composer\Capability\Patcher\BasePatcherProvider`, or `\cweagans\Composer\Capability\Resolver\BaseResolverProvider` for a `Downloader` provider, a `Patcher` provider, or a `Resolver` provider respectively.

## Capability classes

Finally, you should implement the actual capability classes that actually provide the functionality that you want to provide. There are base classes provided that you can extend to make this process easier - `\cweagans\Composer\Downloader\DownloaderBase`, `\cweagans\Composer\Patcher\PatcherBase`, and `\cweagans\Composer\Resolver\ResolverBase` for a `Downloader`, `Patcher`, or `Resolver` respectively. Otherwise, you will need to implement the appropriate interface (each of which lives in the same namespace as the corresponding base class).
