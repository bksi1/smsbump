<?php

namespace app\web\base;

use ArrayAccess;
use ArrayIterator;
use Countable;
/**
 * HeadersCollection represents a collection of HTTP headers.
 *
 * @property array $headers The actual headers. This property is read-only.
 * @property array $originalHeaderNames The original header names. This property is read-only.
 */
class HeadersCollection implements \IteratorAggregate, ArrayAccess, Countable
{
    private $_headers = [];
    private $_originalHeaderNames = [];

    /**
     * Returns an iterator for traversing the headers.
     * @return ArrayIterator an iterator for traversing the headers.
     */
    public function getIterator():  ArrayIterator
    {
        return new ArrayIterator($this->_headers);
    }

    /**
     * Returns the number of headers.
     * @return int the number of headers
     */
    public function count(): int
    {
        return $this->getCount();
    }

    /**
     * Returns the number of headers.
     * @return int the number of headers
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_headers[$offset]);
    }

    /**
     * Returns the number of headers.
     * @return int the number of headers
     */
    public function getCount(): int
    {
        return count($this->_headers);
    }

    /**
     * Returns the actual headers.
     * @param string $name the header name
     * @param string|null $default the value to be returned if the named header does not exist
     * @param bool $first whether to only return the first header of the specified name.
     * @return string|null the actual headers
     */
    public function get(string $name, ?string $default = null, bool $first = true): ?string
    {
        $normalizedName = strtolower($name);
        if (isset($this->_headers[$normalizedName])) {
            return $first ? reset($this->_headers[$normalizedName]) : $this->_headers[$normalizedName];
        }

        return $default;
    }

    /**
     * @param $name
     * @param $value
     * @return HeadersCollection
     */
    public function set($name, $value = ''): HeadersCollection
    {
        $normalizedName = strtolower($name);
        $this->_headers[$normalizedName] = (array) $value;
        $this->_originalHeaderNames[$normalizedName] = $name;

        return $this;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function remove($name): mixed
    {
        $normalizedName = strtolower($name);
        if (isset($this->_headers[$normalizedName])) {
            $value = $this->_headers[$normalizedName];
            unset($this->_headers[$normalizedName], $this->_originalHeaderNames[$normalizedName]);
            return $value;
        }

        return null;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->_headers;
    }

    /**
     * @param array $array
     */
    public function fromArray(array $array): void
    {
        foreach ($array as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Removes all headers.
     */
    public function removeAll()
    {
        $this->_headers = [];
        $this->_originalHeaderNames = [];
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name): bool
    {
        return isset($this->_headers[strtolower($name)]);
    }

    /**
     * @param $name
     * @param $value
     * @return HeadersCollection
     */
    public function add($name, $value): HeadersCollection
    {
        $normalizedName = strtolower($name);
        $this->_headers[$normalizedName][] = $value;
        if (!\array_key_exists($normalizedName, $this->_originalHeaderNames)) {
            $this->_originalHeaderNames[$normalizedName] = $name;
        }

        return $this;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetGet(mixed $offset): bool
    {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }
}