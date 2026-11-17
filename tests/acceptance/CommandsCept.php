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
$I->seeInThisFile('4dc9f5061770f76d203942a3a7f211fe6bbcbde58a185605afc038002f538c9f');

$I->runShellCommand('composer patches-relock');
$I->openFile('patches.lock.json');
$I->seeInThisFile('4dc9f5061770f76d203942a3a7f211fe6bbcbde58a185605afc038002f538c9f');

$I->runShellCommand('composer patches-repatch 2>&1');
$I->canSeeInShellOutput('Removing cweagans/composer-patches-testrepo');
$I->canSeeInShellOutput('Installing cweagans/composer-patches-testrepo');
$I->canSeeInShellOutput('Patching cweagans/composer-patches-testrepo');
$I->seeFileFound('./vendor/cweagans/dev-dummy-package/LICENSE.md');

$I->runShellCommand('composer patches-repatch --install-options="--non-existent-option" 2>&1', false);
$I->seeResultCodeIsNot(0);
$I->dontSeeInShellOutput('Removing cweagans/composer-patches-testrepo');
$I->seeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');

$I->deleteFile('vendor/autoload.php');
$I->runShellCommand('composer prp --install-options="--no-dev --no-autoloader" 2>&1');
$I->seeInShellOutput('Removing cweagans/composer-patches-testrepo');
$I->seeInShellOutput('Installing cweagans/composer-patches-testrepo');
$I->seeInShellOutput('Patching cweagans/composer-patches-testrepo');
$I->dontSeeInShellOutput('Generating autoload files');
$I->seeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
$I->dontSeeFileFound('./vendor/cweagans/dev-dummy-package/LICENSE.md');
$I->dontSeeFileFound('./vendor/autoload.php');
