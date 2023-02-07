<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('see the plugin complain loudly if no patchers are available');
$I->amInPath(codecept_data_dir('fixtures/no-patchers-available'));
$I->runComposerCommand('install', ['-vvv'], false);
$I->seeComposerStatusCodeIsNot(0);
$I->canSeeInComposerOutput('No available patcher was able to apply patch');
$I->cantSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
