<?php

namespace app\core\db;

use app\core\Container;
use Exception;
use PDO;

class Command
{
    public $_sql;
    protected $fetchMode = PDO::FETCH_ASSOC;
    public Connection $db;
    public $pdoStatement;
    public $pendingParams = [];
    public $params = [];

    /**
     * @param array $values
     * @return Command
     */
    public function bindValues($values): Command
    {
        if (! empty($values)) {
            foreach ($values as $name => $value) {
                if (is_array($value)) {
                    $this->pendingParams[$name] = $value;
                    $this->params[] = $value[0];
                } else {
                    $this->bindValue($name, $value);
                }
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param int|null $dataType
     * @return Command
     */
    public function bindValue($name, $value, $dataType = null): Command
    {
        $this->pendingParams[$name] = [$value, $dataType];
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * @return DataReader
     * @throws Exception
     */
    public function query(): DataReader
    {
        return $this->queryInternal('');
    }

    /**
     * @param string $fetchMode
     * @return array
     * @throws Exception
     */
    public function queryAll($fetchMode = null): array
    {
        return $this->queryInternal('fetchAll', $fetchMode);
    }

    /**
     * @param $sql
     * @return Command
     */
    public function setSql($sql): Command
    {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->reset();
            $this->_sql = $this->db->quoteSql($sql);
        }

        return $this;
    }

    /**
     * @param $sql
     * @return Command
     */
    public function setRawSql($sql): Command
    {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->reset();
            $this->_sql = $sql;
        }

        return $this;
    }

    /**
     * @return void
     */
    public function cancel(): void
    {
        $this->pdoStatement = null;
    }

    /**
     * @return void
     */
    protected function reset(): void
    {
        $this->_sql = null;
        $this->pendingParams = [];
        $this->params = [];
    }

    /**
     * @param string $fetchMode
     * @return array|false
     * @throws Exception
     */
    public function queryOne($fetchMode = null): array|false
    {
        return $this->queryInternal('fetch', $fetchMode);
    }

    /**
     * @return mixed
     */
    public function queryScalar(): mixed
    {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function logQuery(): array
    {
        return [true, isset($rawSql) ? $rawSql : $this->getRawSql()];
    }

    /**
     * @return string
     */
    public function getRawSql(): string
    {
        if (empty($this->params)) {
            return $this->_sql;
        }
        $params = [];
        foreach ($this->params as $name => $value) {
            if (is_string($name) && strncmp(':', $name, 1)) {
                $name = ':' . $name;
            }
            if (is_string($value)) {
                $params[$name] = $this->db->quoteValue((string)$value);
            } elseif (is_bool($value)) {
                $params[$name] = ($value ? 'TRUE' : 'FALSE');
            } elseif ($value === null) {
                $params[$name] = 'NULL';
            } elseif (!is_object($value) && !is_resource($value)) {
                $params[$name] = $value;
            }
        }
        if (!isset($params[1])) {
            return preg_replace_callback('#(:\w+)#', function($matches) use ($params) {
                $m = $matches[1];
                return isset($params[$m]) ? $params[$m] : $m;
            }, $this->_sql);
        }
        $sql = '';
        foreach (explode('?', $this->_sql) as $i => $part) {
            $sql .= (isset($params[$i]) ? $params[$i] : '') . $part;
        }

        return $sql;
    }

    /**
     * @return string
     */
    public function getSql(): string
    {
        return $this->_sql;
    }

    /**
     * @param $forRead
     * @return void
     * @throws Exception
     */
    public function prepare($forRead = null): void
    {
        if ($this->pdoStatement) {
            $this->bindPendingParams();
            return;
        }

        $sql = $this->getSql();
        if ($sql === '') {
            return;
        }

        $pdo = $this->db->getPdo();

        try {
            $this->pdoStatement = $pdo->prepare($this->_sql);
            $this->bindPendingParams();
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $this->_sql";
            $errorInfo = $e instanceof \PDOException ? $e->errorInfo : null;
            throw new Exception($message, $errorInfo, $e->getCode(), $e);
        } catch (\Throwable $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $this->_sql";
            throw new Exception($message, null, $e->getCode(), $e);
        }
    }

    /**
     * @return void
     */
    protected function bindPendingParams(): void
    {
        foreach ($this->pendingParams as $name => $value) {
            $this->pdoStatement->bindValue($name, $value[0], $value[1]);
        }
        $this->pendingParams = [];
    }

    public function update($table, $columns, $condition = '', $params = [])
    {
        $sql = $this->db->getQueryBuilder()->update($table, $columns, $condition, $params);
        return $this->setSql($sql)->bindValues($params);
    }

    /**
     * @return int|string|null
     * @throws Exception
     */
    public function execute(): int|string|null
    {
        $this->prepare();
        $this->internalExecute($this->getRawSql());
        $n = $this->pdoStatement->rowCount();
        return $n > 0 ? $this->db->pdo->lastInsertId() : null;
    }

    /**
     * @param $method
     * @param $fetchMode
     * @return mixed
     * @throws Exception
     */

    protected function queryInternal($method, $fetchMode = null): mixed
    {
        list($profile, $rawSql) = $this->logQuery();

        $this->prepare(true);
        try {
            $this->internalExecute($rawSql);

            if ($method === '') {
                $result = new DataReader($this);
            } else {
                if ($fetchMode === null) {
                    $fetchMode = $this->fetchMode;
                }
                $result = call_user_func_array([$this->pdoStatement, $method], (array) $fetchMode);
                $this->pdoStatement->closeCursor();
            }
        } catch (Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * @param $rawSql
     * @return void
     * @throws Exception
     */
    protected function internalExecute($rawSql): void
    {
        try {
            $this->pdoStatement = $this->db->getPdo()->prepare($this->_sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $this->pdoStatement->execute($this->params);
        } catch (\Exception $e) {
            $rawSql = $rawSql ?? $this->getRawSql();
            $logMessage = $e->getMessage() . "\nFailed to execute SQL: $rawSql";
            Container::getInstance()->get('log')->log($logMessage);
            throw $e;
        }
    }
}