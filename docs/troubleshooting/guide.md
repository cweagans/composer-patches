---
title: Guide
weight: 10
---

{{< lead text="Common problems that people have run into and how to fix them." >}}

## System readiness

If you've encountered a problem, a good first step is to run [`composer patches-doctor`]({{< relref "../usage/commands.md#composer-patches-doctor" >}}). This will run a few checks against your system and look for common configuration errors.


## Upgrade system software

See the [system requirements]({{< relref "../getting-started/system-requirements.md" >}}) page for the minimum supported versions of PHP, Composer, and other software. Upgrade your software using the method appropriate for your operating system.


## Install patching software

Composer Patches requires at least _some_ mechanism for applying patches. If you don't have any installed, you'll see a fair number of errors. You should install some combination of GNU `patch`, BSD `patch`, `git`, or other applicable software. macOS users commonly need to `brew install gpatch` to get a modern version of `patch` on their system.


## Set `preferred-install` to `source`

The Git patcher included with this plugin is the most reliable method of applying patches. However, the Git patcher won't even _attempt_ to apply a patch if the target directory isn't cloned from Git. To avoid this situation, you should either set `"preferred-install": "source"` or set specific packages to be installed from source in your Composer configuration. Patches may still be successfully applied without this setting, but if you're working on a team, you'll have much more consistent results with the Git patcher.


## Download patches securely

If you've been referred here, you're trying to download patches over HTTP without explicitly telling Composer that you want to do that. See the [`secure-http`]({{< relref "../usage/configuration.md#secure-http" >}}) documentation for more information.

