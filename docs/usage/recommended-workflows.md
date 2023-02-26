---
title: Recommended Workflows
weight: 40
---

{{< lead text="Common workflows for working with Composer Patches on a team." >}}

## Initial setup

The plugin can safely be [installed]({{< relref "../getting-started/installation.md" >}}) as part of initial project setup, even if you don't have any patches to apply right away. A `patches.lock.json` will still be written, but it will be empty.

## Add a patch to your project

1. [Define a patch]({{< relref "defining-patches.md" >}}) in your `composer.json` or your external patches file (either will work by default, but choose the appropriate place based on how your project is configured).
2. Run [`composer patches-relock`]({{< relref "commands.md#composer-patches-relock" >}}) to regenerate `patches.lock.json` with your new patch.
3. Run [`composer patches-repatch`]({{< relref "commands.md#composer-patches-repatch" >}}) to delete patched dependencies and reinstall them with any defined patches {{< warning title="Running `composer patches-repatch` will delete data" >}}
Ensure that you don't have any unsaved changes in any patched dependencies in your project.
{{< /warning >}}
4. If your patch definition was added to `composer.json`, run `composer update --lock` to update the content hash in `composer.lock`.
5. Commit any related changes to your external patches file (if configured), `composer.json`, `composer.lock`, and `patches.lock.json`.

## Apply patches added to the project by someone else

If you have an existing copy of the project and you're updating it to include someone else's changes:

1. Pull changes from your project's version control system.
2. Run [`composer patches-repatch`]({{< relref "commands.md#composer-patches-repatch" >}}) {{< warning title="Running `composer patches-repatch` will delete data" >}}
Ensure that you don't have any unsaved changes in any patched dependencies in your project.
{{< /warning >}}

If you're installing the project from scratch:

1. Clone the project
2. Run `composer install`

## Remove a patch

1. Delete the patch definition from your `composer.json` or external patches file.
2. Run [`composer patches-relock`]({{< relref "commands.md#composer-patches-relock" >}}) to regenerate `patches.lock.json` with your new patch.
3. Manually delete the dependency that you removed a patch from (the location of the dependency will vary by project, but a good starting point is to look in the `vendor/` directory).
4. Run [`composer patches-repatch`]({{< relref "commands.md#composer-patches-repatch" >}}) to delete patched dependencies and reinstall them with any defined patches {{< warning title="Running `composer patches-repatch` will delete data" >}}
Ensure that you don't have any unsaved changes in any patched dependencies in your project.
{{< /warning >}}
5. If your patch definition was removed from  `composer.json`, run `composer update --lock` to update the content hash in `composer.lock`.
6. Commit any related changes to your external patches file (if configured), `composer.json`, `composer.lock`, and `patches.lock.json`.


