<?php

namespace cweagans\Composer\Tests;

use Codeception\Actor;
use Codeception\Lib\Friend;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Define custom actions here
     */

    public function runComposerInstall()
    {
        $env = '';
        if (getenv('COMPOSER_PATCHES_DEBUG') !== false) {
            $env = 'COMPOSER_ALLOW_XDEBUG=1 XDEBUG_SESSION=1 XDEBUG_MODE=debug ';
        }

        $this->runShellCommand($env . 'composer install -vvv');
        $this->seeResultCodeIs(0);
    }
}
