<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('see an error when I try to apply a patch that does not apply');
$I->amInPath(codecept_data_dir('fixtures/patch-does-not-apply'));
$I->runComposerCommand('install', ['-vvv'], false);
$I->seeInComposerOutput("Couldn't apply patch.");
$I->seeInComposerOutput("Please check if you can apply the patch manually.");
$I->seeInComposerOutput("Exception trace");
