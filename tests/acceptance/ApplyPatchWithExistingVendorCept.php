<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('apply a patch when the vendor directory contains an older patch');
$I->amInPath(realpath(__DIR__ . '/fixtures/patches-existing-vendor-root'));
$I->runShellCommand('composer install');
$I->runShellCommand('cp -av web vendor ../patches-existing-vendor-update');
$I->amInPath(realpath(__DIR__ . '/fixtures/patches-existing-vendor-update'));
$I->runShellCommand('COMPOSER_DISCARD_CHANGES=1 composer install');
//$I->canSeeFileFound('./vendor/drupal/drupal/.ht.router.php');
