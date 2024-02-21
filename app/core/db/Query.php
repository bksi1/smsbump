<?php

namespace app\core\db;

use app\core\Container;
use app\core\Model;

/**
 * Class Query
 * Abstract class for building SQL query
 *
 * @package app\core\db
 * @property string $sql
 * @property array $from
 * @property array $select
 * @property bool $distinct
 * @property array $where
 * @property int $limit
 * @property int $offset
 * @property array $orderBy
 * @property array $groupBy
 * @property array $having
 * @property array $union
 * @property array $params
 * @property string $indexBy
 *
 */
class Query
{
    public $sql;
    public array|string $from;
    public array $select;
    public array|bool $distinct;
    public array $where;
    public array|null $join;
    public int|null $limit;
    public int|null $offset;
    public array|null $orderBy;
    public array|null $groupBy;
    public array|null $having;
    public array|null $union;
    public array|null $params;
    public string|null $indexBy;
    public $selectOption;
    private string $modelClass;

    public function __construct($config = [])
    {
        foreach ($config as $key => $value) {
            if (!isset($this, $key)) {
                continue;
            }
            $this->$key = $value;
        }
    }

    /**
     * @param ?string $db
     * @return array
     */
    public function all(?string $db = null): array
    {
        $rows = $this->createCommand($db)->queryAll();

        return $this->populate($rows);
    }

    /**
     * @param string|array $tables
     * @return $this
     */
    public function from(string|array $tables): self
    {
        if (is_string($tables)) {
            $tables = preg_split('/\s*,\s*/', trim($tables), -1, PREG_SPLIT_NO_EMPTY);
        }
        $this->from = $tables;
        return $this;
    }

    /**
     * @param array $condition
     * @param array $params
     * @return $this
     */
    public function where(array $condition,array $params = []): self
    {
        $this->where = $condition;
        $this->addParams($params);
        return $this;
    }

    /**
     * @param array $condition
     * @param array $params
     * @return $this
     */
    public function andWhere(array $condition,array $params = []): self
    {
        if ($this->where === null) {
            $this->where = $condition;
        } elseif (is_array($this->where) && isset($this->where[0]) && strcasecmp($this->where[0], 'and') === 0) {
            $this->where[] = $condition;
        } else {
            $this->where = ['and', $this->where, $condition];
        }
        $this->addParams($params);
        return $this;
    }

    /**
     * @param array $condition
     * @param array $params
     * @return $this
     */
    public function orWhere(array $condition,array $params = []): self
    {
        if ($this->where === null) {
            $this->where = $condition;
        } else {
            $this->where = ['or', $this->where, $condition];
        }
        $this->addParams($params);
        return $this;
    }

    public function count($q = '*', $db = null)
    {
        return $this->queryScalar("COUNT($q)", $db);
    }

    /**
     * Prepares for building SQL.
     * This method is called by [[QueryBuilder]] when it starts to build SQL from a query object.
     * You may override this method to do some final preparation work when converting a query into a SQL statement.
     * @param QueryBuilder $builder
     * @return $this a prepared query instance which will be used by [[QueryBuilder]] to build the SQL
     */
    public function prepare(QueryBuilder $builder)
    {
        // override this method to provide preparation just before query building
        return $this;
    }

    /**
     * @param array $params
     * @return Query
     */
    public function addParams(array $params): Query
    {
        if (!empty($params)) {
            if (empty($this->params)) {
                $this->params = $params;
            } else {
                foreach ($params as $name => $value) {
                    if (is_int($name)) {
                        $this->params[] = $value;
                    } else {
                        $this->params[$name] = $value;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param string $selectExpression
     * @param Connection $db
     * @return mixed
     */
    protected function queryScalar(string $selectExpression,?Connection $db): mixed
    {
        $this->select = [$selectExpression];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;

        $command = $this->createCommand($db);

        return $command->queryScalar();
    }

    /**
     * @param Connection|null $db
     * @return Command
     */
    public function createCommand(?Connection $db = null): Command
    {
        if ($db === null) {
            $db = Container::getInstance()->get('db');;
        }
        list($sql, $params) = $db->getQueryBuilder()->build($this);

        $command = $db->createCommand($sql, $params);

        return $command;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function populate(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $model = new $this->modelClass;
            $model->setIsNewRecord(false);
            $model->load($row);
            if ($this->indexBy === null) {
                $result[] = $model;
            } else {
                $result[$this->indexBy] = $model;
            }
        }

        return $result;
    }

    /**
     * @param Connection $db
     * @return Model|false
     */
    public function one(?Connection $db = null): Model|false
    {
        $model = false;
        $result = $this->createCommand($db)->queryOne();
        if ($result) {
            $model = new $this->modelClass;
            $model->setIsNewRecord(false);
            $model->load($result);
        }
        return $model;
    }

    /**
     * @param array $columns
     * @return Query
     */
    public function orderBy(array $columns): Query
    {
        $this->orderBy = $columns;
        return $this;
    }

    /**
     * @param $config
     * @return Query
     */
    public static function create($config): Query
    {
        return new Query([
            'select' => $config['select'] ?? [],
            'from' => $config['from'] ?? [],
            'where' => $config['where'] ?? [],
            'limit' => $config['limit'] ?? null,
            'offset' => $config['offset'] ?? null,
            'orderBy' => $config['orderBy'] ?? null,
            'join' => $config['join'] ?? null,
            'groupBy' => $config['groupBy'] ?? null,
            'distinct' => $config['distinct'] ?? false,
            'having' => $config['having'] ?? null,
            'union' => $config['union'] ?? null,
            'indexBy' => $config['indexBy'] ?? null,
            'params' => $config['params'] ?? [],
            'modelClass' => $config['modelClass'] ?? null,
        ]);
    }

    /**
     * Returns the SQL representation of Query
     * @return string
     */
    public function __toString(): string
    {
        return serialize($this);
    }

}