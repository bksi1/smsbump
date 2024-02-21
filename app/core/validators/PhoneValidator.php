<?php

namespace app\core\validators;

/**
 * Class PhoneValidator
 * @package app\core\validators
 */
class PhoneValidator extends BaseValidator
{
    private string $_errorMessage;

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->_errorMessage;
    }

    /**
     * @return bool
     */
    public function validate() : bool
    {
        $value = $this->model->{$this->attribute};
        if (!preg_match('/^\d{11,15}$/', $value)) {
            $this->_errorMessage = 'Invalid phone number';
            return false;
        }
        return true;
    }
}