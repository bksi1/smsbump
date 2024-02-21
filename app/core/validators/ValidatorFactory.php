<?php

namespace app\core\validators;

use app\core\Model;
use Throwable;

/**
 * Class ValidatorFactory
 * @package app\core\validators
 * Creates validator by type
 */
class ValidatorFactory
{
    /**
     * @param string $type
     * @param Model $model
     * @param string $attribute
     * @return BaseValidator
     */
    public static function createValidator(string $type, Model $model, string $attribute): BaseValidator
    {
        $className =  ucfirst($type) . 'Validator';
        $fullClassName = __NAMESPACE__ . '\\' . $className;
        try {
            $classInstance = new $fullClassName($model, $attribute);
            if ($classInstance instanceof BaseValidator) {
                return $classInstance;
            }
        } catch (Throwable $e) {
            return new DefaultValidator($model, $attribute);
        }

        return new DefaultValidator($model, $attribute);
    }
}