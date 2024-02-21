<?php

namespace app\core\validators;

/**
 * Class RequiredValidator
 * @package app\core\validators
 */
class RequiredValidator extends BaseValidator
{
    /**
     * @inheritDoc
     */
    public function validate(): bool
    {
        $value = $this->model->{$this->attribute};
        return !empty($value);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): string
    {
        return $this->attribute.' is required';
    }
}