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
$I->seeInThisFile('4dc9f5061770f76d203942a3a7f211fe6bbcbde58a185605afc038002f538c9f');

$I->deleteFile('composer-a-patches.lock.json');
$I->runShellCommand('composer patches-relock');
$I->canSeeFileFound('./composer-a-patches.lock.json');
$I->openFile('composer-a-patches.lock.json');
$I->seeInThisFile('4dc9f5061770f76d203942a3a7f211fe6bbcbde58a185605afc038002f538c9f');

// Clean up so other tests don't fail.
putenv('COMPOSER');
