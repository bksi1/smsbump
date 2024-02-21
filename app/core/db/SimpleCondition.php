<?php

namespace app\core\db;

use app\core\exceptions\InvalidArgumentException;
use app\core\interfaces\ConditionInterface;

/**
 * Class SimpleCondition
 * @package app\core\db
 * @property string $operator
 * @property string $column
 * @property string $value
 */
class SimpleCondition implements ConditionInterface
{
    private Connection $db;
    private $operator;
    private string $column;
    private string $value;

    public function __construct($db, $column, $operator, $value)
    {
        $this->db = $db;
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $operator
     * @param array $operands
     * @throws InvalidArgumentException if wrong number of operands have been given.
     */
    public static function fromArrayDefinition(string $operator,array $operands, Connection $db = null): SimpleCondition
    {
        if (count($operands) !== 2) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands.");
        }

        return new SimpleCondition($db, $operands[0], $operator, $operands[1]);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $value = ! empty($this->db) ? $this->db->quoteValue($this->value) : $this->value;
        return $this->column . ' ' . $this->operator . ' ' . $value;
    }

}