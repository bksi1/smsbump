<?php

namespace app\core\validators;

use app\core\Model;

/**
 * Class BaseValidator
 * @package app\core\validators
 */
abstract class BaseValidator
{
    protected Model $model;
    protected string $attribute;
    public function __construct($model, $attribute)
    {
        $this->model = $model;
        $this->attribute = $attribute;
    }

    /**
     * @return bool
     */
    public abstract function validate() : bool;

    /**
     * @return string
     */
    public abstract function getErrorMessage(): string;

}