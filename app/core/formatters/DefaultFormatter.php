<?php

namespace app\core\formatters;

/**
 * Class DefaultFormatter
 * @package app\core\formatters
 */
class DefaultFormatter extends BaseFormatter
{

    public function format(mixed $value): mixed
    {
        return $value;
    }
}