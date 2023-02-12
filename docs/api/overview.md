---
title: Overview
weight: 10
---

In previous versions of Composer Patches, the plugin only had a certain set of functionality that couldn't really be modified. Now, much of the behavior of the plugin is accessible through APIs exposed to developers.

## Capabilities

A _capability_ is the name that Composer has given to individual components that implement a particular interface. For example, a Composer plugin might be capable of providing additional commands to the Composer console application. This process is [documented by composer](https://getcomposer.org/doc/articles/plugins.md#plugin-capabilities).

Composer Patches has declared a few additional capabilities that allow third-party plugins to extend the ability to find, download, and apply patches. Additional information may be found in the [Capabilities]({{< relref "capabilities.md" >}}) documentation.

## Events

An _event_ is emitted for various operations within the plugin. This allows you to hook into the process of resolving, downloading, or applying patches and perform some other action unrelated to the core functionality of the plugin. In some cases, you can alter data before handing it back to Composer Patches.
