<?php

namespace app\core\db;

use app\core\exceptions\InvalidArgumentException;
use app\core\interfaces\ConditionInterface;

/**
 * Class CompareCondition
 * @package app\core\db
 */
class CompareCondition implements ConditionInterface
{
    private Connection $db;
    private $operator;
    private string $column;
    private string $value;
    private $compareSign;

    public function __construct(Connection $db, $column, $operator, $compareSign, $value)
    {
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
        $this->compareSign = $compareSign;
        $this->db = $db;
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
    public static function fromArrayDefinition(string $operator,array $operands, Connection $db = null): ConditionInterface
    {
        if (count($operands) < 2) {
            return new HashCondition($db, $operands);
        }
        if (count($operands) !== 3) {
            throw new InvalidArgumentException("Operator '$operator' requires two operands. And compare sign");
        }

        return new CompareCondition($db, $operator, $operands[1], $operands[0], $operands[2]);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $value = ! empty($this->db) ? $this->db->quoteValue($this->value) : $this->value;
        return $this->column . ' ' . $this->operator . ' '. $this->compareSign . ' ' . $value;
    }

}