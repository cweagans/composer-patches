<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('know that the dependency patches will be applied if it matches the wildcard');
$I->amInPath(codecept_data_dir('fixtures/dependency-patches-allow-wildcard'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeInComposerOutput('Patching cweagans/composer-patches-testrepo');
$I->seeFileFound('OneMoreTest.php', 'vendor/cweagans/composer-patches-testrepo/src');
$I->openFile('patches.lock.json');
$I->seeInThisFile('0ec56d93aed447775aa70e55b5530f401cb3a59facd8ce20301c1d007461f1bf');
