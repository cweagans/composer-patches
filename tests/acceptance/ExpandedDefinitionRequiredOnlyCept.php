<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('apply a patch to a package using the expanded definition format (required props only)');
$I->amInPath(codecept_data_dir('fixtures/expanded-definition-required-only'));
$I->runComposerInstall();
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
