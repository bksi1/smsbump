<?php

namespace app\core\exceptions;

/**
 * Class InvalidConfigException
 * @package app\core\exceptions
 */
class InvalidConfigException extends \Exception
{
    public function getName(): string
    {
        return 'Invalid Configuration';
    }
}