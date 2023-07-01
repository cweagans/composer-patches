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

Composer Patches requires `git` to be installed in order to apply patches. Previous versions of the plugin relied on
various versions of `patch`, but that is no longer the case. Make sure you have `git` installed and you should be all set.


## Download patches securely

If you've been referred here, you're trying to download patches over HTTP without explicitly telling Composer that you want to do that. See the [`secure-http`]({{< relref "../usage/configuration.md#secure-http" >}}) documentation for more information.
