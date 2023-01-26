<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet');
$I->amInPath(codecept_data_dir('fixtures/apply-patch-from-web'));
$I->runShellCommand('composer install');
$I->canSeeFileFound('./vendor/drupal/core/.ht.router.php');
