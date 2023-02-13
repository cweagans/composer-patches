---
title: Freeform Patcher
weight: 30
---

{{< lead text="The core patchers try to take care of as many cases as possible, but sometimes you need custom behavior." >}}

Composer Patches now includes a "freeform patcher", which essentially lets you define a patcher and its arguments in your patch definition.

## Usage

To use the freeform patcher, you must use the [expanded format]({{< relref "defining-patches.md#expanded-format" >}}) for your patch definition. You'll need to add a few extra values to the `extra` key in your patch definition like so:

```json
{
    [...],
    "extra": {
        "patches": {
            "the/project": [
                {
                    "description": "This is another patch",
                    "url": "https://www.example.com/different/path/to/file.patch"
                    "depth": 123
                    "extra": {
                        "freeform": {
                            "executable": "/path/to/your/executable",
                            "args": "--verbose %s %s %s",
                        }
                    },
                },
            ]
        }
    }
}
```

If the `executable` and `args` values are not populated, the freeform patcher will not perform any work.

The `%s` placeholders will be populated by escaped arguments that are always provided in this order:

1. Patch depth
2. Path to the location on disk where the dependency was installed
3. Local path to the patch file

It is not possible to change the order of these arguments, but you can always create a small wrapper script in your project and handle the arguments how you'd like.

In this example, the command that would be run by the patcher is
```shell
/path/to/your/executable --verbose '/full/path/to/vendor/the/project' '123' '/full/path/to/file.patch`
```

{{< callout title="$PATH will be searched for executables" >}}
If the executable you want to run is included in your `$PATH`, you do not have to specify the full path to the executable.
{{< /callout >}}

## Dry run

If your patcher is capable of testing whether or not a patch can be applied (for instance, `patch` can do this with the `--dry-run` argument), you can also supply a set of dry run arguments that will be run first:

```json
{
    [...],
    "extra": {
        "patches": {
            "the/project": [
                {
                    "description": "This is another patch",
                    "url": "https://www.example.com/different/path/to/file.patch"
                    "depth": 123
                    "extra": {
                        "freeform": {
                            "executable": "/path/to/your/executable",
                            "args": "--verbose %s %s %s",
                            "dry_run_args": "--verbose --dry-run %s %s %s"
                        }
                    },
                },
            ]
        }
    }
}
```

The arguments will be provided in the same order as the `args` value.

## Tips

### Exit codes matter

If your tool returns an exit code of `0`, Composer Patches will assume that the patch was applied correctly (or that the dry run was successful and the patch should be applied). If it returns anything other than `0`, Composer Patches will assume that the patch was unsuccessful (or that the dry run indicated that attempting to apply the patch would be unsuccessful).

### Always run in verbose mode

Any patcher you configure here should always include the `--verbose` flag (or whatever your patcher's equivalent is). The output will not be printed to the console during normal operation, but if you are running composer with the `--verbose` flag (for instance, with `composer install --verbose` or `composer install -v`), the output will be printed so that you can see what was happening.
