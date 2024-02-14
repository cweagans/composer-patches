<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('know that the plugin will handle duplicate patches appropriately');
$I->amInPath(codecept_data_dir('fixtures/dependency-patches-duplicate-patch'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeInComposerOutput('Resolving patches from root package');
$I->canSeeInComposerOutput('Resolving patches from dependencies');
$I->canSeeInComposerOutput('Patching cweagans/composer-patches-testrepo');
$I->seeFileFound('OneMoreTest.php', 'vendor/cweagans/composer-patches-testrepo/src');
$I->openFile('patches.lock.json');
$I->seeInThisFile('4dc9f5061770f76d203942a3a7f211fe6bbcbde58a185605afc038002f538c9f');
