---
title: Commands
weight: 50
---

## `composer patches-relock`
**Alias**: `composer prl`

`patches-relock` causes the plugin to re-discover all available patches and then write them to the `patches.lock` file for your project. This command should be used when changing the list of patches in your project. See the [recommended workflows]({{< relref "recommended-workflows.md" >}}) page for details.

---

## `composer patches-repatch`
**Alias**: `composer prp`

`patches-repatch` causes all patched dependencies to be deleted and re-installed, which causes patches to be re-applied to those dependencies. This command should be used when changing the list of patches in your project. See the [recommended workflows]({{< relref "recommended-workflows.md" >}}) page for details.

---

## `composer patches-doctor`
**Alias**: `composer pd`

`patches-doctor` runs a few checks against your system in an attempt to find common issues that people run into when using Composer Patches. A report will be emitted along with a handful of suggestions that may help you. The output of this command is required when submitting a bug report.
