<?php

namespace app\core\formatters;

/**
 * Class FormatterFactory
 * Creates formatter by type
 * @package app\core\formatters
 */
class FormatterFactory
{
    public static function createFormatter(string $type): BaseFormatter
    {
        $className = ucfirst($type) . 'Formatter';
        $fullClassName = __NAMESPACE__ . '\\' . $className;
        if (class_exists($fullClassName)) {
            return new $fullClassName();
        }
        return new DefaultFormatter();
    }

}