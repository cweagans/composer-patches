<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('apply a patch when the vendor directory contains an older patch');
$I->amInPath(realpath(__DIR__ . '/fixtures/patches-existing-vendor-root'));
$I->runShellCommand('composer install -n');
$I->runShellCommand('mv web vendor ../patches-existing-vendor-update');
$I->amInPath(realpath(__DIR__ . '/fixtures/patches-existing-vendor-update'));
$I->runShellCommand('COMPOSER_DISCARD_CHANGES=1 composer install -n');
$I->canSeeFileFound('./web/modules/contrib/migrate_upgrade/drush.services.yml');
