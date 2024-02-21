<?php

namespace app\core;

use app\core\exceptions\InvalidConfigException;
use Exception;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Class Container
 * Singleton class used as a dependency injection container
 * @package app\core
 */
class Container
{
    private array $_singletons = [
        'db' => 'app\core\db\Connection',
        'log' => 'app\components\LogFactory',
    ];
    private array $_params = [];
    private array $_reflections = [];
    private array $_dependencies = [];
    private static $_instance;

    private function __construct() {
        //singleton
    }

    /**
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (empty(self::$_instance)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * @param string $definition
     * @param array $params
     * @param array $config
     * @return mixed
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function get($definition, $params = [], $config = []): mixed
    {
        $classPath =  $params['class'] ?? "";
        unset($params['class']);

        if (is_object($definition)) {
            $this->_singletons[$classPath] = $definition;
        }

        if (isset($this->_singletons[$definition]) && is_object($this->_singletons[$definition])) {
            // singletons
            return $this->_singletons[$definition];
        }

        if (is_array($definition) && ! empty($classPath)) {
            $config = array_merge($definition, $config);
            $params = $this->mergeParams($classPath, $params);
            $object = $this->get($classPath, $params, $config);
        } elseif (is_callable($classPath, true)) {
            $params = $this->resolveDependencies($this->mergeParams($classPath, $params));
            $object = $this->build($classPath, $params, $config);
        } else if (is_string($definition)) {
            $object = $this->build($classPath, $params, $config);
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($classPath));
        }

        if (array_key_exists($definition, $this->_singletons)) {
            // singleton
            $this->_singletons[$definition] = $object;
        }

        return $object;
    }

    /**
     * @param string $class
     * @param array $params
     * @param array|null $config
     * @return mixed
     * @throws Exception
     */
    protected function build(string $class,array $params,?array $config): mixed
    {
        /* @var $reflection ReflectionClass */
        list($reflection, $dependencies) = $this->getDependencies($class);

        $addDependencies = [];
        if (isset($config['__construct()'])) {
            $addDependencies = $config['__construct()'];
            unset($config['__construct()']);
        }
        foreach ($params as $index => $param) {
            $addDependencies[$index] = $param;
        }

        if ($addDependencies && is_int(key($addDependencies))) {
            $dependencies = array_values($dependencies);
            $dependencies = $this->mergeDependencies($dependencies, $addDependencies);
        } else {
            $dependencies = $this->mergeDependencies($dependencies, $addDependencies);
            $dependencies = array_values($dependencies);
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (!$reflection->isInstantiable()) {
            throw new Exception($reflection->name);
        }
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        $config = $this->resolveDependencies($config);

        if (!empty($dependencies)) {
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        }

        $object = $reflection->newInstanceArgs($dependencies);
        foreach ($config as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * @param array $dependencies
     * @param ReflectionClass|null $reflection
     * @return array
     * @throws InvalidConfigException
     */
    protected function resolveDependencies(array $dependencies,?ReflectionClass $reflection = null): array
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency && isset($dependency->id)) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $dependency->get($this);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            } elseif (is_array($dependency)) {
                $dependencies[$index] = $this->resolveDependencies($dependency, $reflection);
            }
        }

        return $dependencies;
    }

    /**
     * @param string $class
     * @param array $params
     */
    protected function getDependencies(string $class): array
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        try {
            $reflection = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new Exception(
                'Failed to instantiate component or class "' . $class . '".',
                0,
                $e
            );
        }

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isVariadic()) {
                    break;
                }

                $c = $param->getType();
                $isClass = false;
                if ($c instanceof ReflectionNamedType) {
                    $isClass = !$c->isBuiltin();
                }

                $className = $isClass ? $c->getName() : null;

                if ($className !== null) {
                    $dependencies[$param->getName()] = new static($className, $this->isNulledParam($param));
                } else {
                    $dependencies[$param->getName()] = $param->isDefaultValueAvailable()
                        ? $param->getDefaultValue()
                        : null;
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * @param string $class
     * @param array $params
     * @return array
     */
    protected function mergeParams(string $class,array $params): array
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        }

        $ps = $this->_params[$class];
        foreach ($params as $index => $value) {
            $ps[$index] = $value;
        }

        return $ps;
    }

    /**
     * @param ReflectionParameter $param
     * @return bool
     */
    private function isNulledParam(ReflectionParameter $param) : bool
    {
        return $param->isOptional() || $param->getType()->allowsNull();
    }

    /**
     * @param array $a
     * @param array $b
     * @return array
     */
    private function mergeDependencies(array $a, array $b): array
    {
        foreach ($b as $index => $dependency) {
            $a[$index] = $dependency;
        }
        return $a;
    }

}