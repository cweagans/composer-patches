<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('use composer commands to repatch and relock patch data');
$I->amInPath(codecept_data_dir('fixtures/commands'));
$I->runComposerCommand('install', ['-vvv']);

$I->openFile('patches.lock.json');
$I->seeInThisFile('725f2631cb6a92c8c3ffc2e396e89f73b726869131d4c4d2a5903aae6854a260');

$I->runShellCommand('composer patches-relock');
$I->openFile('patches.lock.json');
$I->seeInThisFile('725f2631cb6a92c8c3ffc2e396e89f73b726869131d4c4d2a5903aae6854a260');

$I->runShellCommand('composer patches-repatch 2>&1');
$I->canSeeInShellOutput('Removing cweagans/composer-patches-testrepo');
$I->canSeeInShellOutput('Installing cweagans/composer-patches-testrepo');
$I->canSeeInShellOutput('Patching cweagans/composer-patches-testrepo');
