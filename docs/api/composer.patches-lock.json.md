---
title: composer.patches-lock.json
weight: 40
---

`composer.patches-lock.json` is the mechanism that Composer Patches now uses to maintain a known-good list of patches to apply to the project. For external projects, the structure of `composer.patches-lock.json` may also be treated as an API. If you're considering `composer.patches-lock.json` as a data source for your project, there are a few things that you should keep in mind:

* `composer.patches-lock.json` should be considered **read-only** for external uses.
* The general structure of `composer.patches-lock.json` will not change. You can rely on a JSON file structured like so:
```json
{
    "_hash": "[the hash]",
    "patches": [{patch definition}, {patch definition}, ...]
}
```
* Each patch definition will look like the [expanded format]({{< relref "../usage/defining-patches.md#expanded-format" >}}) that users can put into their `composer.json` or external patches file.
* No _removals_ or _changes_ will be made to the patch definition object. _Additional_ keys may be created, so any JSON parsing you're doing should be tolerant of new keys.
* The `extra` object in each patch definition may contain a number of attributes set by other projects or by the user and should be treated as free-form input.
