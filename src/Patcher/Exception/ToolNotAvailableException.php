<?php

namespace cweagans\Composer\Patcher\Exception;

use Exception;

class ToolNotAvailableException extends Exception
{
    public function __construct($tool)
    {
        parent::__construct("$tool not installed", 0, null);
    }
}
