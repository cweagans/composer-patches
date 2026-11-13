<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('know that the dependency patches will not be applied if the patch dependency has been ignored');
$I->amInPath(codecept_data_dir('fixtures/dependency-patches-ignore'));
$I->runComposerCommand('install', ['-vvv']);
// phpcs:ignore Generic.Files.LineLength.TooLong
$I->canSeeInComposerOutput('Skipping patches from the cweagans/dep-test-package dependency because it is within the ignore-dependency-patches list.');
$I->canSeeInComposerOutput('No patches found for cweagans/composer-patches-testrepo');
