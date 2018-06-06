<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('preserve applied patches when updating other dependencies');
$I->amInPath(realpath(__DIR__ . '/fixtures/patches-applied-preserved'));
$I->runShellCommand('composer install');
$I->runShellCommand('composer update drupal/pathauto');
$I->openFile('composer.lock');
$I->canSeeInThisFile('Support using tempstore when there is no session');
