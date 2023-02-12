---
title: Patches.lock
weight: 40
---

`patches.lock` is the mechanism that Composer Patches now uses to maintain a known-good list of patches to apply to the project. For external projects, the structure of `patches.lock` may also be treated as an API. If you're considering `patches.lock` as a data source for your project, there are a few things that you should keep in mind:

* `patches.lock` should be considered **read-only** for external uses.
* The general structure of `patches.lock` will not change. You can rely on a JSON file structured like so:
```json
{
    "_hash": "[the hash]",
    "patches": [{patch definition}, {patch definition}, ...]
}
```
* Each patch definition will look like the [expanded format]({{< relref "../usage/defining-patches.md#expanded-format" >}}) that users can put into their `composer.json` or external patches file.
* No _removals_ or _changes_ will be made to the patch definition object. _Additional_ keys may be made, so any JSON parsing you're doing should be tolerant of new keys.
* The `extra` object in each patch definition may contain a number of attributes set by other projects. The core plugin will not do anything with that data beyond reading/writing it to/from `patches.lock` and you probably shouldn't either (unless you were the one responsible for putting it there in the first place).
