<?php

namespace app\core\db;

use app\core\interfaces\ConditionInterface;

/**
 * Class HashCondition
 * @package app\core\db
 */
class HashCondition implements ConditionInterface
{
    private Connection $db;
    private ?array $hash;

    public function __construct(Connection $db,array $hash)
    {
        $this->db = $db;
        $this->hash = $hash;
    }

    /**
     * @return array|null
     */
    public function getHash(): ?array
    {
        return $this->hash;
    }

    /**
     * @param string $operator
     * @param array $operands
     * @return ConditionInterface
     */
    public static function fromArrayDefinition(string $operator,array $operands, Connection $db = null): ConditionInterface
    {
        return new static($db, $operands);
    }

    /**
     * Custom object serializer
     * @return string
     */
    public function __toString(): string
    {
        $sql = "";
        foreach ($this->hash as $key => $value) {
            $sql .= $key . " = " . $this->db->quoteValue($value);
        }
        return $sql;
    }

}