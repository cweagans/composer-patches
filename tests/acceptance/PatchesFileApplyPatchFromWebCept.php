<?php

/**
 * @var \Codeception\Scenario $scenario
 */

use cweagans\Composer\Tests\AcceptanceTester;

$I = new AcceptanceTester($scenario);
$I->wantTo('modify a package using a patch downloaded from the internet (defined in patches file)');
$I->amInPath(codecept_data_dir('fixtures/patches-file-patch-from-web'));
$I->runShellCommand('composer install');
$I->canSeeFileFound('./vendor/drupal/core/.ht.router.php');
