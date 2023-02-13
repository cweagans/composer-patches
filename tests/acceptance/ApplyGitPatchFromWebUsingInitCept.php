<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet (exercising Git init patcher)');
$I->amInPath(codecept_data_dir('fixtures/apply-git-patch-from-web-using-init'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
