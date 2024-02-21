<?php

namespace app\core\db;

use Countable;
use Exception;
use Iterator;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 */
class DataReader implements Iterator, Countable
{
    private $_statement;
    private $_closed = false;
    private $_row;
    private $_index = -1;

    public function __construct(Command $command)
    {
        $this->_statement = $command->pdoStatement;
        $this->_statement->setFetchMode(\PDO::FETCH_ASSOC);
    }

    /**
     * Binds a column to a PHP variable.
     * @param string $column Number of the column (1-indexed) or name of the column in the result set. If using the column name, be aware that the name should match the case of the column, as returned by the driver.
     * @param mixed $value Value of the PHP variable to which the column will be bound.
     * @param int $dataType Data type of the parameter
     * @return void
     */
    public function bindColumn(string $column,mixed &$value,string $dataType = null): void
    {
        if ($dataType === null) {
            $this->_statement->bindColumn($column, $value);
        } else {
            $this->_statement->bindColumn($column, $value, $dataType);
        }
    }

    /**
     * @param mixed $mode
     * @return void
     */
    public function setFetchMode(mixed $mode): void
    {
        $params = func_get_args();
        call_user_func_array([$this->_statement, 'setFetchMode'], $params);
    }

    /**
     * Fetches the next row from a result set.
     * @return mixed The next row of the result set, or false if there is no more row.
     */
    public function read(): mixed
    {
        return $this->_statement->fetch();
    }

    /**
     * Fetches the next row and returns a single column from the result set.
     * @param int $columnIndex 0-based index of the column to return
     * @return mixed The value of the requested column. If there is no value, null is returned.
     */
    public function readColumn(int $columnIndex): mixed
    {
        return $this->_statement->fetchColumn($columnIndex);
    }

    /**
     * Fetches the next row and returns an object of the specified class.
     * @param string $className Name of the created class
     * @param array $fields Elements of this array are passed to the constructor
     * @return mixed The newly created object
     */
    public function readObject(string $className,array $fields): mixed
    {
        return $this->_statement->fetchObject($className, $fields);
    }

    /**
     * Fetches all rows from the result set as an array.
     * @return array The result set (each array element represents a row of data).
     */
    public function readAll(): array
    {
        return $this->_statement->fetchAll();
    }

    /**
     * Fetches the next row and returns it as an associative array.
     * @return array The fetched row as an associative array, or an empty array if there is no more row.
     */
    public function nextResult(): array
    {
        if (($result = $this->_statement->nextRowset()) !== false) {
            $this->_index = -1;
        }

        return $result;
    }

    /**
     * Closes the reader.
     */
    public function close(): void
    {
        $this->_statement->closeCursor();
        $this->_closed = true;
    }

    /**
     * Returns a value indicating whether the reader is closed.
     * @return bool Whether the reader is closed
     */
    public function getIsClosed(): bool
    {
        return $this->_closed;
    }

    /**
     * Returns the number of rows in the result set.
     * @return int The number of rows in the result set
     */
    public function getRowCount(): int
    {
        return $this->_statement->rowCount();
    }

    /**
     * Returns the number of columns in the result set.
     * @return int The number of columns in the result set
     */
    public function getColumnCount(): int
    {
        return $this->_statement->columnCount();
    }

    /**
     * Set the iterator pointer to the first element.
     * @return void
     * @throws Exception
     */
    public function rewind(): void
    {
        if ($this->_index < 0) {
            $this->_row = $this->_statement->fetch();
            $this->_index = 0;
        } else {
            throw new Exception('DataReader cannot rewind. It is a forward-only reader.');
        }
    }

    /**
     * Returns the index of the current row.
     * @return int The index of the current row
     */
    public function key(): int
    {
        return $this->_index;
    }

    /**
     * Returns the current row.
     * @return mixed The current row
     */
    public function current(): mixed
    {
        return $this->_row;
    }

    /**
     * Moves the iterator pointer to the next row in the result set.
     * @return void
     */
    public function next(): void
    {
        $this->_row = $this->_statement->fetch();
        $this->_index++;
    }

    /**
     * Returns a value indicating whether there is a row of data at current position.
     * @return bool Whether there is a row of data at current position
     */
    public function valid(): bool
    {
        return $this->_row !== false;
    }

    /**
     * Returns the number of rows in the result set.
     * @return int number of rows in the result set
     */
    public function count(): int
    {
        return $this->getRowCount();
    }

}