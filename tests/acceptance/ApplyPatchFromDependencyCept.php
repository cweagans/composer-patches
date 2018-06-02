<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('apply a patch defined in a dependency');
$I->amInPath(realpath(__DIR__ . '/fixtures/apply-patch-from-dependency'));
$I->runShellCommand('composer install');
$I->canSeeFileFound('./vendor/drupal/drupal/.ht.router.php');
