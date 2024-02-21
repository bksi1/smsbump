<?php

namespace app\core\db;

use Exception;

/**
 * Class QueryBuilder
 * Build text SQL query from Query object
 *
 * @package app\core\db
 * @property Connection $db
 * @property string $separator
 */
class QueryBuilder
{
    const PARAM_PREFIX = ':qp';
    public $separator = ' ';

    public Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param Query $query
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function build(Query $query, array $params = []): array
    {
        $query = $query->prepare($this);

        $params = empty($params) ? $query->params : array_merge($params, $query->params);

        $clauses = [
            $this->buildSelect($query->select, $query->distinct, $query->selectOption),
            $this->buildFrom($query->from, $params),
            $this->buildJoin($query->join, $params),
            $this->buildWhere($query->where, $params),
            $this->buildGroupBy($query->groupBy),
            $this->buildHaving($query->having, $params),
        ];

        $sql = implode($this->separator, array_filter($clauses));
        $sql = $this->buildOrderByAndLimit($sql, $query->orderBy, $query->limit, $query->offset);

        return [$sql, $params];
    }

    /**
     * @param array $columns
     * @param bool $distinct
     * @param string|null $selectOption
     * @return string
     */
    public function buildSelect(array $columns,bool $distinct = false,?string $selectOption = null): string
    {
        $select = $distinct ? 'SELECT DISTINCT' : 'SELECT';
        if ($selectOption !== null) {
            $select .= ' ' . $selectOption;
        }

        if (empty($columns)) {
            return $select . ' *';
        }

        foreach ($columns as $i => $column) {
            if (is_string($i) && $i !== $column) {
                if (strpos($column, '(') === false) {
                    $column = $this->db->quoteColumnName($column);
                }
                $columns[$i] = "$column AS " . $this->db->quoteColumnName($i);
            } elseif (strpos($column, '(') === false) {
                if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)([\w\-_\.]+)$/', $column, $matches)) {
                    $columns[$i] = $this->db->quoteColumnName($matches[1]) . ' AS ' . $this->db->quoteColumnName($matches[2]);
                } else {
                    $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
        }

        return $select . ' ' . implode(', ', $columns);
    }

    /**
     * @param array|string $tables
     * @param array $params
     * @return string
     */
    public function buildFrom(array|string $tables,array &$params): string
    {
        if (empty($tables))
            return '';

        $tables = $this->quoteTableNames($tables, $params);

        return 'FROM ' . implode(', ', $tables);
    }

    /**
     * @param array|null $joins
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function buildJoin(?array $joins,array &$params): string
    {
        if (empty($joins))
            return '';

        foreach ($joins as $i => $join) {
            if (!is_array($join) || !isset($join[0], $join[1])) {
                throw new Exception('A join clause must be specified as an array of join type, join table, and optionally join condition.');
            }
            // 0:join type, 1:join table, 2:on-condition (optional)
            list($joinType, $table) = $join;
            $tables = $this->quoteTableNames((array)$table, $params);
            $table = reset($tables);
            $joins[$i] = "$joinType $table";
            if (isset($join[2])) {
                $condition = $this->buildCondition($join[2], $params);
                if ($condition !== '') {
                    $joins[$i] .= ' ON ' . $condition;
                }
            }
        }

        return implode($this->separator, $joins);
    }

    /**
     * @param array $condition
     * @param array $params
     * @return string
     */
    public function buildWhere(array $condition,array &$params): string
    {
        $where = $this->buildCondition($condition, $params);

        return $where === '' ? '' : 'WHERE ' . $where;
    }

    /**
     * @param array|null $columns
     * @return string
     */
    public function buildGroupBy(?array $columns): string
    {
        if (empty($columns)) {
            return '';
        }
        foreach ($columns as $i => $column) {
            if (strpos($column, '(') === false) {
                $columns[$i] = $this->db->quoteColumnName($column);
            }
        }

        return 'GROUP BY ' . implode(', ', $columns);
    }

    /**
     * @param array|null $condition
     * @param array $params
     * @return string
     */
    public function buildHaving(?array $condition,array &$params): string
    {
        $having = $this->buildCondition($condition, $params);

        return $having === '' ? '' : 'HAVING ' . $having;
    }

    /**
     * @param string|null $sql
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return string
     */
    public function buildOrderByAndLimit(?string $sql,?array $orderBy,?int $limit,?int $offset): string
    {
        $orderBy = $this->buildOrderBy($orderBy);
        if ($orderBy !== '') {
            $sql .= $this->separator . $orderBy;
        }
        $limit = $this->buildLimit($limit, $offset);
        if ($limit !== '') {
            $sql .= $this->separator . $limit;
        }

        return $sql;
    }

    /**
     * @param array|null $columns
     * @return string
     */
    public function buildOrderBy(?array $columns): string
    {
        if (empty($columns)) {
            return '';
        }
        $orders = [];
        foreach ($columns as $name => $direction) {
            $orders[] = $this->db->quoteColumnName($name) . ($direction === SORT_DESC ? ' DESC' : '');
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @return string
     */
    public function buildLimit(?int $limit,?int $offset): string
    {
        $sql = '';
        if ($this->hasLimit($limit)) {
            $sql = 'LIMIT ' . $limit;
        }
        if ($this->hasOffset($offset)) {
            $sql .= ' OFFSET ' . $offset;
        }

        return ltrim($sql);
    }

    /**
     * Checks to see if the given limit is effective.
     * @param mixed $limit the given limit
     * @return bool whether the limit is effective
     */
    protected function hasLimit(mixed $limit): bool
    {
        return ctype_digit((string)$limit) || (is_int($limit) && $limit >= 0);
    }

    /**
     * Checks to see if the given offset is effective.
     * @param mixed $offset the given offset
     * @return bool whether the offset is effective
     */
    protected function hasOffset(mixed $offset): bool
    {
        return ctype_digit((string)$offset) && (string)$offset !== '0' || (is_int($offset) && $offset > 0);
    }

    /**
     * @param mixed $condition
     * @param array $params
     * @return string
     */
    public function buildCondition(mixed $condition,array &$params): string
    {
        if (is_array($condition)) {
            if (empty($condition)) {
                return '';
            }

            if (isset($condition[0])) { // operator format: operator, operand 1, operand 2, ...
                $operator = strtoupper(array_shift($condition));
                if(! is_array($condition[0])) {
                    $condition = SimpleCondition::fromArrayDefinition($operator, $condition, $this->db);
                } else {
                    $conditionInfo = [];
                    foreach ($condition as $i => $operand) {
                        if (is_array($operand)) {
                            $conditionInfo[$i] = (string) CompareCondition::fromArrayDefinition($operator, $operand, $this->db);
                        }
                    }
                    $condition = implode(" ", $conditionInfo);

                }
            } else {
                // hash format: 'column1' => 'value1', 'column2' => 'value2', ...
                $condition = new HashCondition($this->db, $condition);
            }
        }

        return (string)$condition;
    }

    /**
     * @param $table
     * @param $columns
     * @param array $params
     * @return array
     */
    protected function prepareInsertValues($columns, $params = []): array
    {
        $names = [];
        $placeholders = [];
        $values = ' DEFAULT VALUES';

        foreach ($columns as $name => $value) {
            if ($value === null)
                continue;
            $names[] = $this->db->quoteColumnName($name);
            $placeholders[] = $this->bindParam($value, $params);
        }

        return [$names, $placeholders, $values, $params];
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $params
     * @return array
     */
    protected function prepareUpdateSets(string $table,array $columns,array $params = []): array
    {
        $sets = [];
        foreach ($columns as $name => $value) {
            $placeholder = $this->bindParam($value, $params);
            $sets[] = $this->db->quoteColumnName($name) . '=' . $placeholder;
        }
        return [$sets, $params];
    }

    /**
     * @param string $value
     * @param array $params
     * @return string
     */
    public function bindParam(string $value,array &$params): string
    {
        $countParams = ! empty($params) ? count($params) : 0;
        $phName = self::PARAM_PREFIX . $countParams;
        $params[$phName] = $value;

        return $phName;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $params
     * @return string
     */
    public function insert(string $table,array $columns, &$params): string
    {
        list($names, $placeholders, $values, $params) = $this->prepareInsertValues($columns, $params);
        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);
        return $sql;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $params
     * @return string
     */
    public function update(string $table,array $columns,mixed $condition, &$params): string
    {
        list($lines, $params) = $this->prepareUpdateSets($table, $columns, $params);
        $sql = 'UPDATE ' . $this->db->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        $where = $this->buildWhere($condition, $params);
        return $where === '' ? $sql : $sql . ' ' . $where;
    }

    /**
     * @param $table
     * @param $columns
     * @param $condition
     * @param array $params
     * @return string
     */
    private function quoteTableNames($tables, &$params): array
    {
        if (!is_array($tables)) {
            return [$this->db->quoteTableName($tables)];
        }
        foreach ($tables as $i => $table) {
            if (is_string($i)) {
                if (strpos($table, '(') === false) {
                    $table = $this->db->quoteTableName($table);
                }
                $tables[$i] = "$table " . $this->db->quoteTableName($i);
            } elseif (strpos($table, '(') === false) {
                if ($tableWithAlias = $this->db->extractAlias($table)) { // with alias
                    $tables[$i] = $this->db->quoteTableName($tableWithAlias[1]) . ' ' . $this->db->quoteTableName($tableWithAlias[2]);
                } else {
                    $tables[$i] = $this->db->quoteTableName($table);
                }
            }
        }

        return $tables;
    }
}