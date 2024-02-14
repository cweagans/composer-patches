<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('know that the plugin will find patches defined in dependencies');
$I->amInPath(codecept_data_dir('fixtures/dependency-patches'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeInComposerOutput('Resolving patches from dependencies');
$I->canSeeInComposerOutput('Patching cweagans/composer-patches-testrepo');
$I->seeFileFound('OneMoreTest.php', 'vendor/cweagans/composer-patches-testrepo/src');
$I->openFile('patches.lock.json');
$I->seeInThisFile('5f539e947d097d79e97006f958916c362df0fd19ba7011b1b54b69f02d5f9958');
