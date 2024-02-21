<?php

namespace app\web\base\exceptions;

use app\core\ConfigHelper;

/**
 * Class HeadersAlreadySentException
 * @package app\web\base\exceptions
 */
class HeadersAlreadySentException extends \Exception
{
    public function __construct($file, $line)
    {
        ConfigHelper::getInstance()->get('log')->log('Headers already sent in ' . $file . ' on line ' . $line . '.', 'error');
        $message = 'Headers already sent.';
        parent::__construct($message);
    }

}