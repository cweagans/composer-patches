<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);

$I->wantTo('apply patches only from a lock file if present');
$I->amInPath(codecept_data_dir('fixtures/apply-patch-from-lock-file'));
$I->runComposerInstall();
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');

// Now that everything is installed, we'll change the patch, nuke the vendor dir, and check that the old patch from the
// lock file is still used (rather than the new one from composer.json).
$I->runShellCommand('rm -r vendor');
$I->runShellCommand('mv composer.json composer_old.json');
$I->runShellCommand('cp composer2.json composer.json');
$I->runComposerInstall();

// TODO: These assertions fail because it's applying the wrong patch.
$I->canSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/OneMoreTest.php');
$I->cantSeeFileFound('./vendor/cweagans/composer-patches-testrepo/src/YetAnotherTest.php');

// Reset composer.json so we don't accidentally commit the new version.
$I->runShellCommand('mv composer_old.json composer.json');
