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

The core plugin used to use multiple tools to try to apply a patch. Unfortunately, that led to a lot of unpredictable
behavior across various environments. Now, the core plugin _only_ relies on Git. As long as you have a relatively recent
version of Git, things should work.

If your Git is older than what is distributed with the current Debian "stable" release, we probably won't be able to do
much with it.
