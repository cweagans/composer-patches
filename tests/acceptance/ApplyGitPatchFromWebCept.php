<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet (exercising Git patcher)');
$I->amInPath(codecept_data_dir('fixtures/apply-git-patch-from-web'));
$I->runComposerInstall();
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
