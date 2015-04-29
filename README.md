# composer-patches

Simple patches plugin for Composer.

## Usage

Example composer.json:

```
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/drupal": "8.0.*@dev"
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches": {
      "drupal/drupal": {
        "Add startup configuration for PHP server": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch"
      }
    }
  }
}

```

Note that the post-package-install part is mandatory for now. For some reason, Composer didn't invoke my event listeners, so we're going with the faster option.

## Difference between this and netresearch/composer-patches-plugin

* This plugin is much more simple to use and maintain
* This plugin doesn't require you to specify which package version you're patching
* This plugin is easy to use with Drupal modules (which don't use semantic versioning).
* This plugin will gather patches from all dependencies and apply them as if they were in the root composer.json

## Credits

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex and difficult to use.
