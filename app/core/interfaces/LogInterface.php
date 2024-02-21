<?php

namespace app\core\interfaces;

/**
 * Interface LogInterface
 * @package app\core\interfaces
 */
interface LogInterface
{
    public function __construct(string $logFileName);
    public function log(string $message);
}