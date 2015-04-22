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
  },
  "scripts": {
    "post-package-install": "cweagans\\Composer\\Patches::postInstallStatic"
  }
}

```

Note that the post-package-install part is mandatory for now. For some reason, Composer didn't invoke my event listeners, so we're going with the faster option.

## Credits

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex and difficult to use.
