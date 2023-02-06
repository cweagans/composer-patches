<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('know that the plugin will work if loaded and not configured');
$I->amInPath(codecept_data_dir('fixtures/no-patches-defined'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeInComposerOutput('No patches found for cweagans/composer-patches-testrepo');
