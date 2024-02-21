<?php

namespace app\core\exceptions;

/**
 * Class InvalidArgumentException
 * @package app\core\exceptions
 */
class InvalidArgumentException extends \Exception
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Invalid Argument';
    }

}