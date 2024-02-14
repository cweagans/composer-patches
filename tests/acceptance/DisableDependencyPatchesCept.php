<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('disable resolving patches from dependencies (and test disabling resolvers in general)');
$I->amInPath(codecept_data_dir('fixtures/disable-dependency-patches'));
$I->runComposerCommand('install', ['-vvv']);
$I->dontSeeInComposerOutput('Resolving patches from dependencies');
$I->dontSeeInComposerOutput('Patching cweagans/commposer-patches-testrepo');
$I->dontSeeFileFound('OneMoreTest.php', 'vendor/cweagans/composer-patches-testrepo/src');
$I->openFile('patches.lock.json');
$I->seeInThisFile('6142bfcb78f54dfbf5247ae5e463f25bdb8fff1890806e2e45aa81a59c211653');
