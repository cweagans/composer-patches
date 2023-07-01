---
title: Limitations
weight: 30
---

{{< lead text="Known limitations will be listed here." >}}

{{< callout title="Create your own plugin" >}}
Be aware that it is possible to create your own plugin to enhance the functionality or overcome known limitations. Additional information may be found in the [Capabilities]({{< relref "capabilities.md" >}}) documentation.
{{< /callout >}}

## Dependencies with patches

When installing a dependency with composer that has its own patch for a dependency this patch will not be applied.

```txt
Root project
|_ dep 1 (containing patch for dep 2)
  |_ dep 2
```

### External patch file

When a dependency defines patches using an external patch file **Composer Patches** will not look into the configuration for patches.

### Patches configured in `composer.json`

**Composer Patches** does look at the patches configuration in the `composer.json` for dependencies.
When these patches are local files, **Composer Patches** will be unable to locate the patch files because composer will install all packages in the root of your project.
As a result relative paths pointing to patch files are now incorrect and will not resolve, resulting in an error.

**Composer Patches** could probable apply patches that are hosted and added via url but this is untested at this point in time.

### Work around

When dependencies have configured patches in the `composer.json` configuration using relative paths you can mirror the file structure in the root of your project using symlinks. This will allow **Composer Patches** to resolve the relative paths and patches will be applied to the dependencies.

```txt
Root project
patches/patch-dep2.patch (symlink -> dep/patches.patch-dep2.patch)
|_ dep
  |_ patches/patch-dep2.patch
|_ dep2
```

Alternatively if symlinking is not an option you could choose to copy a patch into your own project and configure your project to apply the patch.
