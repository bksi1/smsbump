<?php

namespace app\core\interfaces;

use app\core\db\Connection;

/**
 * Interface ConditionInterface
 * @package app\core\interfaces
 */
interface ConditionInterface
{
    public static function fromArrayDefinition(string $operator,array $operands, Connection $db = null): ConditionInterface;

}