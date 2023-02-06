<?php

namespace cweagans\Composer\Tests;

use Codeception\Actor;
use Codeception\Lib\Friend;
use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

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
        $input = new ArrayInput(['command' => 'install', '-vvv']);
        $application = new Application();
        $application->setAutoExit(false);
        $status = $application->run($input);
    }

    public function skipThisTest($reason)
    {
        $this->scenario->skip($reason);
    }
}
