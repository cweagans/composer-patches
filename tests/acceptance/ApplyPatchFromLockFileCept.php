<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('apply patches only from a lock file if present');
$I->amInPath(codecept_data_dir('fixtures/apply-patch-from-lock-file'));
$I->runShellCommand('composer install');
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
$I->runShellCommand('cp composer2.json composer.json');
$I->runShellCommand('composer install');
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
$I->cantSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/YetAnotherTest.php');
