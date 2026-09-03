<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet (with "composer --no-cache")');
$I->amInPath(codecept_data_dir('fixtures/apply-git-patch-from-web-no-cache'));
$I->runComposerCommand('install', ['-vvv', '--no-cache']);
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
