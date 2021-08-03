# composer-patches

Simple patches plugin for Composer. Applies a patch from a local or remote file to any package required with composer.

Note that the 1.x versions of Composer Patches are supported on a best-effort
basis due to the imminent release of 2.0.0. You may still be interested in
using 1.x if you need Composer to cooperate with earlier PHP versions. No new
features will be added to 1.x releases, but any security or bug fixes will
still be accepted.

## Usage

Example composer.json:

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/drupal": "~8.2"
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

## Using an external patch file

Instead of a patches key in your root composer.json, use a patches-file key.

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/drupal": "~8.2"
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

If your project doesn't supply any patches of its own, but you still want to accept patches from dependencies, you must have the following in your composer file:

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

If you do have a `patches` section in your composer file that defines your own set of patches then the `enable-patching` setting will be ignored and patches from dependencies will always be applied.

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
    "drupal/drupal": "~8.2",
    "drupal/lightning": "~8.1"
  },
  "config": {
    "preferred-install": "source"
  },
  "extra": {
    "patches": {
      "drupal/drupal": {
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

### Sub-package Ignoring Patches

In the event you need the patches-ignore of dependency packages to be relevant, the following settings will allow for these to bubble up.

```json
{
  "require": {
    "cweagans/composer-patches": "~1.0",
    "drupal/drupal": "~8.2",
    "drupal/lightning": "~8.1"
  },
  "extra": {
    "enable-patches-ignore-subpackages": true,
    "patches-ignore-whitelist": [
      "drupal/lightning",
      "drupal/core"
    ]
  }
}
```

In the example provided, the settting ```enable-patches-ignore-subpackages``` set to ```TRUE``` which enables the ```patches-ignore``` from dependency packages to be collected and collated into the final patch list. You will also note, that we didn't include a ```patches-ignore``` section. This is completely optional at this point. Should you include it, it will be consumed and calculated into the final aggregated patch ignore list as usual.

 A new section has been added: ```patches-ignore-whitelist```. This list of packages creates a whitelist of ```patches-ignore``` you wish to have consumed as opposed to allowing ALL dependencies to be factored in. This is a good way to limit the playing field.

 This option really becomes useful when the dependencies for a project, which are managed independently of the main ```composer.json```, will be updated from upstream sources and having to maintain the main ```composer.json``` would lead to doubling up of efforts.

 Consider a drupal distro as a dependency of a project which has patches and ```patches-ignore```.  You might like to take advantage of the distro but do not wish to duplicate its list in your project's ```composer.json``` file. With these options, you can allow the maintenance of these patch values to be handled upstream. You can even add another dependency to add or remove patches by including a new package and whitelisting. This can help to decentralize the controls and keep the main ```composer.json``` file clean of continual patch file management.

## Allowing to force the patch level (-pX)

Some situations require to force the patchLevel used to apply patches on a particular package.
Its useful for packages like drupal/core which packages only a subdir of the original upstream project on which patches are based.

```json
{
  "extra": {
    "patchLevel": {
      "drupal/core": "-p2"
    }
  }
}
```

## Patch File Logging

To help manage the potential onslaught of patches and patches-ignore files, the introduction of a logging option should help to review what the plugin is aggregating. You will get a full list of all patches requested, a list of patches to be ignored as well as the final outcome of the patch files removed from the full aggregated list. Use this to review if all the requested files have been properly consumed.

```json
{
  "extra": {
    "patches-log": {
      "location": "patches",
      "format": "txt"
    }
  }
}
```

The ```format``` parameter can be either ```yml```, ```json```, ```txt``` or ```php``` as output formats.

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
1. Export `COMPOSER_EXIT_ON_PATCH_FAILURE=1`

By default, failed patches are skipped.

## Patches reporting

When a patch is applied, the plugin writes a report-file `PATCHES.txt` to a patching directory (e.g. `./patch-me/PATCHES.txt`),
which contains a list of applied patches.

If you want to avoid this behavior, add a specific key to the `extra` section:
```json
"extra": {
    "composer-patches-skip-reporting": true
}
```

Or provide an environment variable `COMPOSER_PATCHES_SKIP_REPORTING` with a config.

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

## Credits

A ton of this code is adapted or taken straight from https://github.com/jpstacey/composer-patcher, which is abandoned in favor of https://github.com/netresearch/composer-patches-plugin, which is (IMHO) overly complex and difficult to use.
