---
title: System Requirements
weight: 10
---

Supporting every version of the software that we depend on is not feasible, so we support a limited, "modern-ish" system
configuration.

## PHP

We follow the list of [currently supported PHP versions](https://www.php.net/supported-versions.php) provided by the
community. Unsupported/EOL versions of PHP will not be supported under any circumstances.

## Composer

Composer 2.2 (LTS) is the minimum version. However, few people run such an old version of Composer and we do not
regularly test functionality with anything other than the current stable release. If you run into problems, we _will_
ask you to try a recent version of Composer before proceeding with troubleshooting.

## Patchers

Most of the patchers that are supported don't change much, but there are a couple version-related items to keep in mind:

* **General**: If you're using a particular program for applying patches, it would be great if it was a version that was
  released in the last few years. As a rule of thumb, if the version of whatever software is older than what is
  distributed with the current Debian "stable" release, we probably won't be able to do much with it.
* **GNU Patch**: Version 2.7 added support for most features of the `diff --git` format and is required for any patches
  that rename/copy files, change permissions, or include symlinks. Future versions of Composer Patches may consider
  lesser versions to be unusable.
