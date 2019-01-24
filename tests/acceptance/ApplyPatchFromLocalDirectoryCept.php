<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Apply a patch from a local directory.');
$I->amInPath(realpath(__DIR__ . '/fixtures/apply-patch-from-local-directory'));
$I->runShellCommand('composer install');
$I->canSeeFileFound(
    realpath(__DIR__ . '/fixtures/apply-patch-from-local-directory/vendor/cweagans/test-package/src/NewFile.php')
);
