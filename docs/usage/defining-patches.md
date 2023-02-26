---
title: Defining Patches
weight: 20
---

## Format

You can describe patches to the plugin in one of two ways: the compact format or the expanded format.

### Compact format

```json
{
    [...],
    "extra": {
        "patches": {
            "the/project": {
                "This is the description of the patch": "https://www.example.com/path/to/file.patch",
                "This is another patch": "https://www.example.com/different/path/to/file.patch"
            }
        }
    }
}
```

This is the format that you may be familiar with from previous versions of Composer Patches. In your `composer.json`, this is an object with descriptions as keys and patch URLs for values. While this format is more convenient, it has some shortcomings - namely that only a description and URL can be specified. If this works for you/your project, you can keep using this format.

### Expanded format

```json
{
    [...],
    "extra": {
        "patches": {
            "the/project": [
                {
                    "description": "This is the description of the patch",
                    "url": "https://www.example.com/path/to/file.patch"
                },
                {
                    "description": "This is another patch",
                    "url": "https://www.example.com/different/path/to/file.patch"
                }
            ]
        }
    }
}
```

Internally, the plugin uses the expanded format for _all_ patches. Similar to the compact format, the only required fields in the expanded format are `description` and `url`. However, in the expanded format, you can specify several other fields:

```json
{
    [...],
    "extra": {
        "patches": {
            "the/project": [
                {
                    "description": "This is the description of the patch",
                    "url": "https://www.example.com/path/to/file.patch",
                    "sha256": "6f024c51ca5d0b6568919e134353aaf1398ff090c92f6173f5ce0315fa266b93",
                    "depth": 2,
                    "extra": {},
                },
                {
                    "description": "This is another patch",
                    "url": "https://www.example.com/different/path/to/file.patch",
                    "sha256": "795a84197ee01b9d50b40889bc5689e930a8839db3d43010e887ddeee643ccdc",
                    "depth": 3,
                    "extra": {
                        "issue-tracker-url": "https://jira.ecorp.com/issues/SM-519"
                    }
                }
            ]
        }
    }
}
```

`sha256` can either be specified in your patch definition as above or the sha256 of a patch file will be calculated and written to your `composer.patches-lock.json` file as part of installation.

`depth` can be specified on a per-patch basis. If specified, this value overrides any other defaults. If not specified, the first available depth out of the following will be used:

1. A package-specific depth set in [`package-depths`]({{< relref "configuration.md#package-depths" >}})
2. Any package-specific depth override set globally in the plugin (see `cweagans\Composer\Util::getDefaultPackagePatchDepth()` for details.)
3. The global [`default-patch-depth`]({{< relref "configuration.md#default-patch-depth" >}})

`extra` is primarily a place for developers to store extra data related to a patch, but if you just need a place to put some extra data about a patch, `extra` is a good place for it. No validation is performed on the contents of `extra`. The [Freeform patcher]({{< relref "freeform-patcher.md" >}}) stores data here.


## Locations

Patches can be defined in multiple places. With the default configuration, you can define plugins in either `composer.json` or a `patches.json` file.

### `composer.json`

As in previous versions of Composer Patches, you can store patch definitions in your root `composer.json` like so:

```json
{
    [...],
    "extra": {
        "patches": {
            // your patch definitions here
        }
    }
}
```
This approach works for many teams, but you should consider moving your patch definitions to a separate `patches.json`. Doing so will mean that you don't have to update `composer.lock` every time you change a patch. Because patch data is locked in `composer.patches-lock.json`, moving the data out of `composer.json` has little downside and can improve your workflow substantially.

### Patches file

If you're defining patches in `patches.json` (or some other separate patches file), the same formats can be used. Rather than nesting patch definitions in the `extra` key in `composer.json`, the plugin expects patches to be defined in a root-level `patches` key like so:

```json
{
    "patches": {
        // your patch definitions here
    }
}
```

## `composer.patches-lock.json`

If `composer.patches-lock.json` does not exist the first time you run `composer install` with this plugin enabled, one will be created for you. Generally, you shouldn't need to do anything with this file: commit it to your project repository alongside your `composer.json` and `composer.lock`, and commit any changes when you change your patch definitions.

This file is similar to `composer.lock` in that it includes a `_hash` and the expanded definition for all patches in your project. When `composer.patches-lock.json` exists, patches will be installed from the locked definitions in this file (_instead_ of using the definitions in `composer.json` or elsewhere).
