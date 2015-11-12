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
      "drupal/drupal": [{
        "url": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch",
        "description": "Add startup configuration for PHP server",
        "sha1": "84c0caf64b2811046d0b325a2fcfe048a83b33fc"
      }]
    }
  }
}

```

## Using an external patch file

Instead of a patches key in your root composer.json, use a patches-file key.

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
    "patches-file": "local/path/to/your/composer.patches.json"
  }
}

```

Then your composer.patches.json should look like this:

```
{
  "patches": {
    "vendor/project": [{
      "url": "http://example.com/url/to/patch.patch",
      "description": "Patch title",
      "sha1": "18deadbeeffea5ta7acafe1defec8db411ef7101"
    }]
  }
}
```

## Difference between this and netresearch/composer-patches-plugin

* This plugin is much more simple to use and maintain
* This plugin doesn't require you to specify which package version you're patching
* This plugin is easy to use with Drupal modules (which don't use semantic versioning).
* This plugin will gather patches from all dependencies and apply them as if they were in the root composer.json

## Credits

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex and difficult to use.
