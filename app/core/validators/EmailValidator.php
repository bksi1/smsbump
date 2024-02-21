<?php

namespace app\core\validators;

/**
 * Class EmailValidator
 * @package app\core\validators
 */
class EmailValidator extends BaseValidator
{
    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return 'Invalid email';
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (!filter_var($this->model->{$this->attribute}, FILTER_VALIDATE_EMAIL)) {
            $this->model->addError($this->attribute, $this->getErrorMessage());
            return false;
        }
        return true;
    }
}