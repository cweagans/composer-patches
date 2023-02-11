---
title: Terminology
weight: 30
---

{{< lead text="There are a few terms specific to this plugin. Understanding them before proceeding may help with understanding." >}}

{{< callout title="This document is for end-users" >}}
If you're looking for developer-focused documentation about each of these components, see the [Capabilities]({{< relref "../api/capabilities.md" >}}) page in the API documentation.
{{< /callout >}}

Resolvers, downloaders, and patchers are all small units of functionality that can be disabled individually or extended by other Composer plugins.

## Resolver

A _resolver_ is a component that looks for patch definitions in a particular place. If any patch definitions are found, they are added to a list maintained internally by the plugin.

An example of a resolver is `\\cweagans\\Composer\\Resolver\\PatchesFile`. If a [`patches-file`]({{< relref "../usage/configuration.md#patches-file" >}}) is configured in `composer.json`, the `PatchesFile` resolver opens the specified patches file, finds any defined patches, and adds them to the list of patches.

## Downloader

A _downloader_ is (intuitively) a component that downloads a patch. The `ComposerDownloader` is the default downloader and uses the same mechanism to download patches that is used by Composer to download packages.

## Patcher

A _patcher_ is a mechanism by which a downloaded patch can be applied to a package. The plugin ships with a handful of patchers in an effort to ensure that a particular system is able to apply a patch _somehow_. Generally, for each system program that is capable of applying a patch (`patch`, `git` (via `git apply`), etc), a `Patcher` can be defined that uses it.


