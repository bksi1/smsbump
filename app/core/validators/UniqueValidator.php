<?php

namespace app\core\validators;

use app\core\validators\BaseValidator;

/**
 * Class UniqueValidator
 * @package app\core\validators
 */
class UniqueValidator extends BaseValidator
{

    /**
     * @inheritDoc
     */
    public function validate(): bool
    {
        $row = $this->model::find([$this->attribute => $this->model->{$this->attribute}])->one();
        return !$row;
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): string
    {
        return $this->attribute.' is already taken';
    }
}