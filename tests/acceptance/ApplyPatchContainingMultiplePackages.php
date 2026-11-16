<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify multiple packages using one patch file only');
$I->amInPath(codecept_data_dir('fixtures/apply-patch-containing-multiple-packages'));
$I->runComposerCommand('install', ['-vvv']);
$I->canSeeFileFound('./vendor/symfony/var-dumper/src/OneMoreTest.php');
$I->canSeeFileFound('./vendor/symfony/var-dumper/src/AnotherTest.php');
$I->canSeeFileFound('./vendor/symfony/yaml/src/OneMoreTest.php');
$I->canSeeFileFound('./vendor/symfony/yaml/src/AnotherTest.php');
