<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet (defined in patches file)');
$I->amInPath(realpath(__DIR__ . '/fixtures/patches-file-patch-from-web'));
$I->runShellCommand('composer install');
$I->canSeeFileFound('./vendor/drupal/core/.ht.router.php');
