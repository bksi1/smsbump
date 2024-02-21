<?php

namespace app\core;

use app\core\db\Connection;
use app\core\db\Query;
use app\core\formatters\FormatterFactory;
use app\core\validators\ValidatorFactory;
use Exception;

/**
 * Class Model
 * Abstract class used as base model for all models
 *
 * @package app\core
 * @property array $_attributes
 * @property array $_privateAttributes
 * @property array $_errors
 * @property bool $isNewRecord
 * @property Connection $db
 */
abstract class Model
{
    private Connection $db;

    private bool $isNewRecord = true;

    protected array $_attributes = [];

    protected array $_privateAttributes = ["password", "code"];
    private $_errors = [];

    public function __construct()
    {
        $this->db = Container::getInstance()->get('db');
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name): mixed
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value): void
    {
        $this->_attributes[$name] = $value;
        $this->$name = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function setIsNewRecord(bool $isNewRecord): void
    {
        $this->isNewRecord = $isNewRecord;
    }

    /**
     * @param array $data
     * @return void
     */
    public function load($data): void
    {
        if (empty($data)) {
            return;
        }
        foreach ($this->_attributes as $key => $value) {
            if (array_key_exists($key, $data)) {
                $this->_attributes[$key] = $data[$key];
                $this->$key = $data[$key];
            } else {
                $this->_attributes[$key] = null;
                $this->$key = null;
            }
        }
    }

    /**
     * @param bool $isNewRecord
     * @throws Exception
     */
    public function beforeSave(bool $isNewRecord): void {
        return;
    }

    /**
     * @return string
     */
    public static function primaryKey(): string
    {
        return 'id';
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return '';
    }

    /**
     * @return Connection
     */
    public static function getDb(): Connection
    {
        return Container::getInstance()->get('db');
    }

    /**
     * @return array
     */
    public function rules() : array
    {
        return [];
    }

    /**
     * @param array|null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate(?array $attributeNames = null, bool $clearErrors = true): bool
    {
        if ($clearErrors) {
            $this->_errors = [];
        }

        if (empty($attributeNames)) {
            $attributeNames = array_keys($this->_attributes);
        }

        $hasErrors = ! empty($this->_errors);

        foreach($attributeNames as $attribute) {
            $rules = $this->rules() ?? [];
            if (empty($rules[$attribute])) {
                continue;
            }

            $this->formatAttribute($attribute, $rules[$attribute]['formatters'] ?? []);

            if (! isset($rules[$attribute]['validators']) || ! is_array($rules[$attribute]['validators'])) {
                continue;
            }
            foreach ($rules[$attribute]['validators'] as $validatorKey) {
                if (!is_string($validatorKey))
                    continue;

                $validatorInstance = ValidatorFactory::createValidator($validatorKey, $this, $attribute);
                if (!$validatorInstance->validate()) {
                    $this->addError($attribute, $validatorInstance->getErrorMessage());
                    $hasErrors = true;
                }
            }

        }

        return !$hasErrors;
    }

/**
     * @param string $attribute
     * @param string[] $formatters
     * @return void
     */
    private function formatAttribute(string $attribute,array $formatters): void
    {
        if (!empty($formatters)) {
            foreach ($formatters as $formatter) {
                $formatterInstance = FormatterFactory::createFormatter($formatter);
                $this->{$attribute} = $this->_attributes[$attribute] = $formatterInstance->format($this->_attributes[$attribute]);
            }
        }
    }

    /**
     * @param string|null $attribute
     * @return bool
     */
    public function hasErrors(?string $attribute = null): bool
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**
     * @param string|null $attribute
     * @return array
     */
    public function getErrors(?string $attribute = null): array
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        }

        return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
    }

    /**
     * @param string $attribute
     * @param string $error
     * @return void
     */
    public function addError(string $attribute,string $error = ''): void
    {
        $this->_errors[$attribute][] = $error;
    }

    /**
     * @param mixed $params
     * @return Query
     */
    public static function find(mixed $params = []): Query
    {
        $config = [];
        $config['from'] = static::tableName();
        $config['where'] = $params;
        $config['modelClass'] = get_called_class();

        return Query::create($config);
    }

    /**
     * @param string $sql
     * @param array $params
     * @return Query
     */
    public static function findBySql(string $sql,array $params = []): Query
    {
        $query = static::find($params);
        $query->sql = $sql;

        return $query;
    }

    /**
     * @param bool $runValidation
     * @return bool
     */
    public function save(bool $runValidation = true): bool
    {
        if ($runValidation && !$this->validate()) {
            return false;
        }
        return $this->isNewRecord ? $this->insert($runValidation) : $this->update($runValidation);
    }

    /**
     * @param bool $runValidation
     * @param array|null $attributes
     * @return bool|int
     */
    public function insert(bool $runValidation = true,?array $attributes = null): bool|int
    {
        if ($runValidation && !$this->validate($attributes)) {
            return false;
        }
        $this->beforeSave(true);
        $insertAttributes = [];
        foreach ($this->_attributes as $name => $value) {
            $insertAttributes[$name] = $attributes[$name] ?? $this->$name;
        }
        $params = [];


        $sql = $this->db->getQueryBuilder()->insert(static::tableName(), $insertAttributes, $params);
        $command = $this->db->createCommand($sql, $params);


        if (($lastId = $command->execute()) === false) {
            return false;
        }

        $idAttribute = $this->getPrimaryKey();
        $this->$idAttribute = $lastId;

        return $lastId;
    }

    /**
     * @param bool $runValidation
     * @param array|null $attributes
     * @return bool|int
     */
    public function update(bool $runValidation = true,?array $attributes = null) : bool|int
    {
        if ($runValidation && !$this->validate($attributes)) {
            return false;
        }

        $this->beforeSave(false);
        $updateAttributes = [];
        foreach ($this->_attributes as $name => $value) {
            if ($name === $this->getPrimaryKey()) {
                continue;
            }
            $updateAttributes[$name] = $attributes[$name] ?? $this->$name;
        }
        $params = [];
        return static::updateAll($updateAttributes, [$this->getPrimaryKey(true) => $this->{$this->getPrimaryKey()}]);
    }

    /**
     * @param array $attributes
     * @param mixed $condition
     * @param array $params
     * @return int|string|null
     */
    public static function updateAll(array $attributes,mixed $condition = '',array $params = []): int|string|null
    {
        $command = static::getDb()->createCommand();
        $command->update(static::tableName(), $attributes, $condition, $params);
        return $command->execute();
    }

    /**
     * @param string $condition
     * @param array $params
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return static::primaryKey();
    }
}