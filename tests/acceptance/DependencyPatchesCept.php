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
$I->seeInThisFile('0c52e193d6ec1047f99ddd32c59c27527e56c0d57bfc3af45b5fe1db0abb077a');
