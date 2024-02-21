<?php

namespace app\core;

/**
 * Class ConfigHelper
 * @package app\core
 */
class ConfigHelper
{
    private static $_instance;
    private function __construct()
    {
        $this->_config = require __DIR__ . '/../config/config.php';
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed
     */
    public function get(string $key, ?string $default = null): mixed
    {
        if (isset($this->_config[$key])) {
            return $this->_config[$key];
        } else {
            return $default;
        }
    }

    public function getAlias(string $alias): string
    {
        if (isset($this->_config['aliases'][$alias])) {
            return $this->_config['aliases'][$alias];
        } else {
            return $alias;
        }
    }

    /**
     * @return ConfigHelper
     */
    public static function getInstance(): ConfigHelper
    {
        if (self::$_instance === null) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getComponent($key): mixed
    {
        $keys = explode('.', $key);
        foreach ($keys as $key) {
            if (isset($this->_config['components'][$key])) {
                $config = $this->_config['components'][$key];
            } else {
                return null;
            }
        }
        return $config;
    }

}