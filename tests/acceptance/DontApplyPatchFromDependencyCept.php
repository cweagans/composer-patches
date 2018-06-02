<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('dont apply a patch defined in a dependency if the dependency patch resolver is disabled');
$I->amInPath(realpath(__DIR__ . '/fixtures/dont-apply-patch-from-dependency'));
$I->runShellCommand('composer install');
$I->cantSeeFileFound('./vendor/drupal/drupal/.ht.router.php');
