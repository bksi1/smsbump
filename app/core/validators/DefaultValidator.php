<?php

namespace app\core\validators;

/**
 * Class DefaultValidator
 * @package app\core\validators
 */
class DefaultValidator extends BaseValidator
{
    /**
     * @return bool
     */
    public function validate(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return '';
    }
}