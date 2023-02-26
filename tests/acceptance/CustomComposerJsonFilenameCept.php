<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('ensure that basic plugin functionality still works when the COMPOSER env var is set');

putenv('COMPOSER=composer-a.json');
$I->amInPath(codecept_data_dir('fixtures/custom-composer-json-filename'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
$I->canSeeFileFound('./composer-a-patches.lock.json');

$I->openFile('composer-a-patches.lock.json');
$I->seeInThisFile('725f2631cb6a92c8c3ffc2e396e89f73b726869131d4c4d2a5903aae6854a260');

$I->deleteFile('composer-a-patches.lock.json');
$I->runShellCommand('composer patches-relock');
$I->canSeeFileFound('./composer-a-patches.lock.json');
$I->openFile('composer-a-patches.lock.json');
$I->seeInThisFile('725f2631cb6a92c8c3ffc2e396e89f73b726869131d4c4d2a5903aae6854a260');

// Clean up so other tests don't fail.
putenv('COMPOSER');
