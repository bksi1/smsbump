<?php

namespace app\core\formatters;

/**
 * Class PhoneFormatter
 * @package app\core\formatters
 */
class PhoneFormatter extends BaseFormatter
{
    public function format(mixed $value): mixed
    {
        $value = preg_replace('/[^0-9]/', '', $value);
        if(substr($value, 0, 1) == '0')
            $value = '359'.substr($value, 1);
        return $value;
    }
}