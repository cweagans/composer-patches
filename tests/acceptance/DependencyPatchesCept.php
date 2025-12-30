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
$I->seeFileFound('relative_test.txt', 'vendor/cweagans/composer-patches-testrepo');
$I->seeFileFound('OneMoreTest.php', 'vendor/cweagans/composer-patches-testrepo/src');
$I->openFile('patches.lock.json');
$I->seeInThisFile('76629a1e5083097f8b0c1ab26db6ead6a0235fc81b3a3aa6b0e2e6237a86dfd3');
$I->seeInThisFile('dependency:cweagans/dep-test-package');
