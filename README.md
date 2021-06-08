# composer-patches

[![Build Status](https://travis-ci.org/cweagans/composer-patches.svg?branch=master)](https://travis-ci.org/cweagans/composer-patches)
[![Coverage Status](https://coveralls.io/repos/github/cweagans/composer-patches/badge.svg?branch=master)](https://coveralls.io/github/cweagans/composer-patches?branch=master)

Simple patches plugin for Composer. Applies a patch from a local or remote file to any package required with composer.

## Support notes

* If you need PHP 5.3, 5.4, or 5.5 support, you should probably use a 1.x release.
* 1.x is mostly unsupported, but bugfixes and security fixes will still be accepted.
  1.7.0 will be the last minor release in the 1.x series.
* Beginning in 2.x, the automated tests will not allow us to use language features
  that will cause syntax errors in PHP 5.6 and later. The unit/acceptance tests do
  not run on anything earlier than PHP 7.1, so while pull requests will be accepted
  for those versions, support is on a best-effort basis.


## Usage

Example composer.json:

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/core-recommended": "^8.8",
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches": {
      "drupal/core": {
        "Add startup configuration for PHP server": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch"
      }
    }
  }
}

```

After modifying `composer.json`, run `composer update --lock` to apply the patch.

## Using an external patch file

Instead of a patches key in your root composer.json, use a patches-file key.

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/core-recommended": "^8.8",
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches-file": "local/path/to/your/composer.patches.json"
  }
}

```

Then your `composer.patches.json` should look like this:

```
{
  "patches": {
    "vendor/project": {
      "Patch title": "http://example.com/url/to/patch.patch"
    }
  }
}
```

## Allowing patches to be applied from dependencies

If you want your project to accept patches from dependencies, you must have the following in your composer file:

```json
{
  "require": {
      "cweagans/composer-patches": "^1.5.0"
  },
  "extra": {
      "enable-patching": true
  }
}
```

## Ignoring patches

There may be situations in which you want to ignore a patch supplied by a dependency. For example:

- You use a different more recent version of a dependency, and now a patch isn't applying.
- You have a more up to date patch than the dependency, and want to use yours instead of theirs.
- A dependency's patch adds a feature to a project that you don't need.
- Your patches conflict with a dependency's patches.

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/core-recommended": "^8.8",
    "drupal/lightning": "~8.1"
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches": {
      "drupal/core": {
        "Add startup configuration for PHP server": "https://www.drupal.org/files/issues/add_a_startup-1543858-30.patch"
      }
    },
    "patches-ignore": {
      "drupal/lightning": {
        "drupal/panelizer": {
          "This patch has known conflicts with our Quick Edit integration": "https://www.drupal.org/files/issues/2664682-49.patch"
        }
      }
    }
  }
}
```

## Using patches from HTTP URLs

Composer [blocks](https://getcomposer.org/doc/06-config.md#secure-http) you from downloading anything from HTTP URLs, you can disable this for your project by adding a `secure-http` setting in the config section of your `composer.json`. Note that the `config` section should be under the root of your `composer.json`.

```json
{
  "config": {
    "secure-http": false
  }
}
```

However, it's always advised to setup HTTPS to prevent MITM code injection.

## Patches containing modifications to composer.json files

Because patching occurs _after_ Composer calculates dependencies and installs packages, changes to an underlying dependency's `composer.json` file introduced in a patch will have _no effect_ on installed packages.

If you need to modify a dependency's `composer.json` or its underlying dependencies, you cannot use this plugin. Instead, you must do one of the following:
- Work to get the underlying issue resolved in the upstream package.
- Fork the package and [specify your fork as the package repository](https://getcomposer.org/doc/05-repositories.md#vcs) in your root `composer.json`
- Specify compatible package version requirements in your root `composer.json`

## Error handling

If a patch cannot be applied (hunk failed, different line endings, etc.) a message will be shown and the patch will be skipped.

To enforce throwing an error and stopping package installation/update immediately, you have two available options:

1. Add `"composer-exit-on-patch-failure": true` option to the `extra` section of your composer.json file.
1. Export `COMPOSER_PATCHES_EXIT_ON_PATCH_FAILURE=1`

By default, failed patches are skipped.

## Patching composer.json in dependencies

This doesn't work like you'd want. By the time you're running `composer install`,
the metadata from your dependencies' composer.json has already been aggregated by
packagist (or whatever metadata repo you're using). Unfortunately, this means that
you cannot e.g. patch a dependency to be compatible with an earlier version of PHP
or change the framework version that a plugin depends on.

@anotherjames over at @computerminds wrote an article about how to work around
that particular problem for a Drupal 8 -> Drupal 9 upgrade:

[Apply Drupal 9 compatibility patches with Composer](https://www.computerminds.co.uk/articles/apply-drupal-9-compatibility-patches-composer) ([archive](https://web.archive.org/web/20210124171010/https://www.computerminds.co.uk/articles/apply-drupal-9-compatibility-patches-composer))

## Difference between this and netresearch/composer-patches-plugin

- This plugin is much more simple to use and maintain
- This plugin doesn't require you to specify which package version you're patching
- This plugin is easy to use with Drupal modules (which don't use semantic versioning).
- This plugin will gather patches from all dependencies and apply them as if they were in the root composer.json

## Contributing
1. `composer install`
1. `vendor/bin/grumphp run`
1. `vendor/bin/codecept run unit`
1. `<write code>`
1. `vendor/bin/grumphp run`
1. `vendor/bin/codecept run unit`
1. `<commit code>`
1. `<create pull request>`


## Credits

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex and difficult to use.
