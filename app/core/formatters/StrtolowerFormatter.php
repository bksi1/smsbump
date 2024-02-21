<?php

namespace app\core\formatters;

/**
 * Class TrimFormatter
 * @package app\core\formatters
 */
class StrtolowerFormatter extends BaseFormatter
{

    /**
     * @inheritDoc
     */
    public function format(mixed $value): mixed
    {
        return strtolower($value);
    }
}