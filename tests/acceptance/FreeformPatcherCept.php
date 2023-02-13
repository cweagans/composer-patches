<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('exercise the freeform patcher');
$I->amInPath(codecept_data_dir('fixtures/freeform-patcher'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
