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
$I->seeFileFound('OneMoreTest.php', 'src');
