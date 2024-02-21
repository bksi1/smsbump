<?php

namespace app\core\db;

use app\core\Container;
use app\core\exceptions\InvalidConfigException;
use Exception;
use PDO;

/**
 * Class Connection
 * @package app\core\db
 *
 * Wrapper class that represents a connection to a database.
 *
 * @attribute string $dsn
 * @attribute string $username
 * @attribute string $password
 * @attribute array $attributes
 * @attribute PDO $pdo
 * @attribute string $charset
 * @attribute string $tablePrefix
 * @attribute string $pdoClass
 * @attribute string $commandClass
 * @attribute array $commandMap
 * @attribute array $schemaMap
 * @attribute string $tableQuoteCharacter
 * @attribute string $columnQuoteCharacter
 * @attribute string $driverName
 * @attribute bool $isActive
 * @attribute QueryBuilder $queryBuilder
 */
class Connection
{
    public $dsn;
    public $username;
    public $password;
    public $attributes;
    public $pdo;
    public $charset;
    public $tablePrefix = '';
    public $pdoClass;
    public $commandClass = 'app\core\db\Command';
    public $commandMap = [
        'mysqli' => 'app\core\db\Command', // MySQL
        'mysql' => 'app\core\db\Command', // MySQL
    ];
    private $_driverName;
    private $_quotedTableNames;
    private $_quotedColumnNames;
    public $tableQuoteCharacter = '`';
    public $columnQuoteCharacter = '`';

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
     * @return bool whether the DB connection is established
     */
    public function getIsActive(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws Exception if connection fails
     */
    public function open(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        if (empty($this->dsn)) {
            throw new InvalidConfigException('Connection::dsn cannot be empty.');
        }

        try {
            $this->pdo = $this->createPdoInstance();
            $this->initConnection();
        } catch (\PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Closes the currently active DB connection.
     * It does nothing if the connection is already closed.
     */
    public function close(): void
    {
        if ($this->pdo !== null) {
            $this->pdo = null;
        }

        $this->_driverName = null;
    }

    /**
     * Creates the PDO instance.
     * When some functionalities are missing in the pdo driver, we should adjust the pdo instance to achieve that
     * functionalities.
     * @return PDO the pdo instance
     */
    protected function createPdoInstance(): PDO
    {
        $pdoClass = $this->pdoClass;
        if ($pdoClass === null) {
            $pdoClass = 'PDO'; // implement strategy pattern here
        }
        $dsn = $this->dsn;

        return new $pdoClass($dsn, $this->username, $this->password, $this->attributes);
    }

    /**
     * Initializes the DB connection.
     * @return void
     */
    protected function initConnection(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);

        if ($this->charset !== null && in_array($this->getDriverName(), ['mysql', 'mysqli'], true)) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }
    }

    /**
     * Creates a command for execution.
     * @param string|null $sql the SQL statement to be executed
     * @param array $params the parameters to be bound to the SQL statement
     * @return Command the DB command
     */
    public function createCommand(string $sql = null,array $params = []): Command
    {
        $driver = $this->getDriverName();
        $config = ['params' => ['class' => 'app\core\db\Command']];
        if ($this->commandClass !== $config['params']['class']) {
            $config['params']['class'] = $this->commandClass;
        } elseif (isset($this->commandMap[$driver])) {
            $config = ['params' => ['class' => $this->commandMap[$driver]]];
        }
        $config['config']['_sql'] = $sql;
        $config['config']['db'] = $this;

        /** @var Command $command */
        $command = Container::getInstance()->get("commmand", $config['params'], $config['config']);
        return $command->bindValues($params);
    }

    /**
     * the driver instance name, null if the connection is not established yet
     * @return string|null
     */
    public function getDriverName(): ?string
    {
        if ($this->_driverName === null) {
            if (($pos = strpos((string)$this->dsn, ':')) !== false) {
                $this->_driverName = strtolower(substr($this->dsn, 0, $pos));
            } else {
                $this->_driverName = strtolower($this->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME));
            }
        }

        return $this->_driverName;
    }

    /**
     * Changes the current driver name.
     * @param string $driverName name of the DB driver
     * @return void
     */
    public function setDriverName($driverName): void
    {
        $this->_driverName = strtolower($driverName);
    }

    /**
     * Returns the PDO instance.
     * @return PDO the PDO instance, null if the connection is not established yet
     */
    public function getPdo(): PDO
    {
        $this->open();
        return $this->pdo;
    }

    /**
     * Returns the query builder for the current DB connection.
     * @return QueryBuilder the query builder for the current DB connection.
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Quotes a table name for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     */
    public function quoteTableName(string $name): string
    {
        if (isset($this->_quotedTableNames[$name])) {
            return $this->_quotedTableNames[$name];
        }
        if (strncmp($name, '(', 1) === 0 && strpos($name, ')') === strlen($name) - 1) {
            return $name;
        }
        if (strpos($name, '{{') !== false) {
            return $name;
        }
        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }
        $parts = $this->getTableNameParts($name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }
        return implode('.', $parts);
    }

    /**
     * Quotes a column name for use in a query.
     * If the column name contains prefix, the table name will also be quoted.
     * @param string $name string to be quoted
     * @return string the properly quoted string
     */
    public function quoteColumnName(string $name): string
    {
        if (isset($this->_quotedColumnNames[$name])) {
            return $this->_quotedColumnNames[$name];
        }

        if (strpos($name, '(') !== false || strpos($name, '[[') !== false) {
            return $name;
        }
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
            $name = substr($name, $pos + 1);
        } else {
            $prefix = '';
        }

        if (strpos($name, '{{') !== false) {
            $returnName = $prefix . $name;
        } else {
            $returnName = $prefix . $this->quoteSimpleTableName($name);
        }

        return $this->_quotedColumnNames[$name] = $returnName;
    }

    /**
     * Splits the table name and the alias.
     * @param string $name the table name
     * @return array the table name and the alias
     */
    protected function getTableNameParts(string $name): array
    {
        return explode('.', $name);
    }

    /**
     * Quotes a simple table name.
     * @param string $name string to be quoted
     * @return string the properly quoted string
     */
    public function quoteSimpleTableName(string $name): string
    {
        if (is_string($this->tableQuoteCharacter)) {
            $startingCharacter = $endingCharacter = $this->tableQuoteCharacter;
        } else {
            list($startingCharacter, $endingCharacter) = $this->tableQuoteCharacter;
        }
        return strpos($name, $startingCharacter) !== false ? $name : $startingCharacter . $name . $endingCharacter;
    }

    /**
     * Extracts the alias part from a table name.
     * @param string $table the table name
     * @return array|false the extracted alias part, or false if the table name does not contain an alias part
     */
    public function extractAlias(string $table): array|false
    {
        if (preg_match('/^(.*?)(?i:\s+as|)\s+([^ ]+)$/', $table, $matches)) {
            return $matches;
        }

        return false;
    }

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $str string to be quoted
     * @return string the properly quoted string
     */
    public function quoteSql(string $sql): string
    {
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) {
                if (isset($matches[3])) {
                    return $this->quoteColumnName($matches[3]);
                }

                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            },
            $sql
        );
    }

    /**
     * Quotes a string value for use in a query.
     * Note that if the parameter is not a string, it will be returned without change.
     * @param string $value string to be quoted
     * @return string the properly quoted string
     */
    public function quoteValue(string $value): string
    {
        if (!is_string($value)) {
            return $value;
        }

        if (mb_stripos($this->dsn, 'odbc:') === false && ($returnValue = $this->getPdo()->quote($value)) !== false) {
            return $returnValue;
        }

        return "'" . addcslashes(str_replace("'", "''", $value), "\000\n\r\\\032") . "'";
    }
}
