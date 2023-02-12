---
title: Non-patchable targets
weight: 20
---

{{< lead text="There are some things that this plugin cannot patch." >}}

## Changes to `composer.json`

Attempting to use a patch to change `composer.json` in a dependency will _never_ work like you want it to.  By the time you're running `composer install`, the metadata from your dependencies' composer.json has already been aggregated by packagist (or whatever metadata repo you're using). Therefore, changes to `composer.json` in a dependency will have _no effect_ on installed packages.

This means that you cannot e.g. patch a dependency to be compatible with an earlier version of PHP or change the framework version that a plugin depends on.

If you need to modify a dependency's `composer.json` or its underlying dependencies, you must do one of the following:

- Work to get the underlying issue resolved in the upstream package.
- Fork the package and [specify your fork as the package repository](https://getcomposer.org/doc/05-repositories.md#vcs) in your root `composer.json`
- Specify compatible package version requirements in your root `composer.json`

@anotherjames over at @computerminds wrote an article about how to work around
that particular problem for a Drupal 8 -> Drupal 9 upgrade:

[James Williams](https://github.com/anotherjames) wrote an article about how to work around this problem for a Drupal 8 -> Drupal 9 upgrade: [Apply Drupal 9 compatibility patches with Composer](https://www.computerminds.co.uk/articles/apply-drupal-9-compatibility-patches-composer) ([archive](https://web.archive.org/web/20210124171010/https://www.computerminds.co.uk/articles/apply-drupal-9-compatibility-patches-composer)). Although it is specific to Drupal, you may be able to use the information to do something more specific to your project/ecosystem.

## Metapackages

Composer has the concept of a "metapackage", which is an empty package that contains requirements and will trigger their installation, but contains no files and will not write anything to the filesystem. Because there is no filesystem path available at all, this plugin is not capable of applying a patch to metapackages.

## Specific dependencies

Some dependencies cannot be patched by this plugin.

### `composer/composer` (installed globally)

Because Composer is typically installed and running on a system long before this plugin is available in a particular project, it is not possible for this plugin to modify Composer itself.

If you have installed Composer locally in your project (by requiring it in the `composer.json` for your project), you _can_ patch the project-level Composer. I'm not entirely sure why you'd want to do this, but it would technically work.

### `cweagans/composer-patches`

Composer Patches applies patches to dependencies as they are installed. If Composer Patches isn't installed, it cannot apply patches to itself as it is installed by Composer. There is no supported workaround for this limitation, as most behavior can be changed via [configuration]({{< relref "../usage/configuration.md" >}}) or through the [API]({{< relref "../api/overview.md" >}}).

### `cweagans/composer-configurable-plugin`

Similarly, because Composer Configurable Plugin is a dependency of Composer Patches (and is therefore installed _before_ Composer Patches), Composer Configurable Plugin cannot be patched. There is no supported workaround for this limitation. If you run into problems with this dependency, open an issue upstream and it will be addressed promptly.
