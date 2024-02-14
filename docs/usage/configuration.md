---
title: Configuration
weight: 10
---

{{< lead text="The plugin ships with reasonable defaults that should work on most environments, but many behaviors are configurable." >}}

## Configuration provided by Composer Patches

### `patches-file`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "patches-file": "mypatches.json"
        }
    }
}
```

**Default value**: `patches.json`

Patch definitions can additionally be loaded from a JSON file. This value should usually be the name of a file located alongside your `composer.json`.

Technically, this value can be a path to a file that is nested in a deeper directory in your project. I don't recommend doing that, as it may cause some confusion if you're using local patches (all paths to local patches will still be relative to the project root where `composer.json` is located).

---

### `package-depths`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "package-depths": {
                "my/package": 3
            }
        }
    }
}
```

**Default value**: empty

`package-depths` allows you to specify overrides for the default patch depth used for a given package. The value for each package is the value that would normally be passed to e.g. a `patch` command: `patch -p3 [...]`.

---

### `ignore-dependency-patches`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "ignore-dependency-patches": [
                "some/package",
            ]
        }
    }
}
```

**Default value**: empty

`ignore-dependency-patches` allows you to ignore patches defined by the listed dependencies. For instance, if your project requires `drupal/core` and `some/package`, and `some/package` defines a patch for `drupal/core`, listing `some/package` in `ignore-dependency-patches` would cause that patch to be ignored. This does _not_ affect the _target_ of those patches. For instance, listing `drupal/core` here would not cause patches _to_ `drupal/core` to be ignored.

---

### `default-patch-depth`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "default-patch-depth": 3
        }
    }
}
```

**Default value**: `1`

`default-patch-depth` changes the default patch depth for **every** package. The default value is usually the right choice (especially if the majority of your patches were created with `git`).

{{< warning title="Change this value with care" >}}
You probably don't need to change this value. Instead, consider setting a package-specific depth in `package-depths` or setting a `depth` on an individual patch.
{{< /warning >}}

---

### `disable-resolvers`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "disable-resolvers": [
                "\\cweagans\\Composer\\Resolver\\RootComposer",
                "\\cweagans\\Composer\\Resolver\\PatchesFile",
                "\\cweagans\\Composer\\Resolver\\Dependencies"
            ]
        }
    }
}
```

**Default value**: empty

`disable-resolvers` allows you to disable individual patch resolvers (for instance, if you want to disallow specifying patches in your root `composer.json`, you might want to add the `\\cweagans\\Composer\\Resolver\\RootComposer` resolver to this list). If a resolver is available and _not_ specified here, it will be used for resolving patches.

For completeness, both of the resolvers that ship with the plugin are listed above, but you should _not_ list both of them unless you don't want **any** patches to be discovered.

After changing this value, you should re-lock and re-apply patches to your project.

---

### `disable-downloaders`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "disable-downloaders": [
                "\\cweagans\\Composer\\Downloader\\ComposerDownloader"
            ]
        }
    }
}
```

**Default value**: empty

`disable-downloaders` allows you to disable individual patch downloaders. If a downloader is available and _not_ specified here, it may be used for downloading patches. 

{{< warning title="Change this value with care" >}}
You probably don't need to change this value unless you're building a plugin that provides an alternative download mechanism for packages.
{{< /warning >}}

---

### `disable-patchers`

```json
{
    [...],
    "extra": {
        "composer-patches": {
            "disable-patchers": [
                "\\cweagans\\Composer\\Patcher\\FreeformPatcher",
                "\\cweagans\\Composer\\Patcher\\GitPatcher",
                "\\cweagans\\Composer\\Patcher\\GitInitPatcher"
            ]
        }
    }
}
```

**Default value**: empty

`disable-patchers` allows you to disable individual patchers. If a patcher is available and _not_ specified here, it may be used to apply a patch to your project.
 
For completeness, all of the patchers that ship with the plugin are listed above, but you should _not_ list all of them. If no patchers are available, the plugin will throw an exception during `composer install`.

`GitPatcher` and `GitInitPatcher` should be enabled and disabled together -- don't disable one without the other.

After changing this value, you should re-lock and re-apply patches to your project.


## Relevant configuration provided by Composer

### `secure-http`

```json
{
    [...],
    "config": {
        "secure-http": false
    }
}
```

**Default value**: `true`

The relevant Composer documentation for this parameter can be found [here](https://getcomposer.org/doc/06-config.md#secure-http).

By default, Composer will block you from downloading anything from plain HTTP URLs. Setting this option will allow you to download data over plain HTTP. Generally, securing the endpoint where you are downloading patches from is a **much better** option. You can also download patches, save them locally, and distribute them with your project as an alternative. Nevertheless, if you really must download patches over plain HTTP, this is the way to do it.

---

### `HTTP_PROXY`

```shell
HTTP_PROXY=http://myproxy:1234 composer install
```

The relevant Composer documentation for this parameter can be found [here](https://getcomposer.org/doc/03-cli.md#http-proxy-or-http-proxy).

If you are using Composer behind an HTTP proxy (common in corporate network environments), setting this value will cause Composer to properly use the specified proxy. If you're using the default `ComposerDownloader` for downloading patches, this setting will be respected and patches will be downloaded through the proxy as well.

---

### `COMPOSER`

```shell
COMPOSER=composer-123.json composer install
```

The relevant Composer documentation for this parameter can be found [here](https://getcomposer.org/doc/03-cli.md#composer).

Some projects require the use of multiple `composer.json` files (along with their respective `composer.lock` and `patches.lock.json`). Composer Patches will create a different `patches.lock.json` file in the event that this environment variable is set. In the example above, `composer-123-patches.lock.json` would be the lock file that is used for patches.
