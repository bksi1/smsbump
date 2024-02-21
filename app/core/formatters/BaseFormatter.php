<?php

namespace app\core\formatters;

abstract class BaseFormatter
{
    /**
     * @param mixed $value
     * @return mixed
     */
    abstract public function format(mixed $value): mixed;
}