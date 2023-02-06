<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet (defined in patches file)');
$I->amInPath(codecept_data_dir('fixtures/patches-file-patch-from-web'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
