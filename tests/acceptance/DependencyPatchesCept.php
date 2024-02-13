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
$I->seeFileFound('OneMoreTest.php', 'src');
