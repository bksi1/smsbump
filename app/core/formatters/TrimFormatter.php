<?php

namespace app\core\formatters;

/**
 * Class TrimFormatter
 * @package app\core\formatters
 */
class TrimFormatter extends BaseFormatter
{

    /**
     * @inheritDoc
     */
    public function format(mixed $value): mixed
    {
        return trim($value);
    }
}