<?php
namespace app\core;

/**
 * Class App
 * @package app\core
 */
abstract class App
{
    protected UriParser $urlParser;
    protected Controller $controller;
    protected Container $container;


    public function __construct($config)
    {
        $this->urlParser = new UriParser();
        $this->container = Container::getInstance();
        $config = ConfigHelper::getInstance();
        foreach ($config->get('components') as $componentName => $settings) {
            $config = $settings['config'] ?? [];
            $params = $settings['params'] ?? [];
            $this->container->get($componentName, $params, $config);
        }
        try {
            $this->container->get('db')->open();
        } catch (\Exception $e) {
            $this->errorExit($e->getMessage());
        }
    }

    /**
     * @return void
     */
    public abstract function run(): void;

    /**
     * @param Route $route
     * @return Controller
     */
    abstract protected function createController(Route $route): Controller;

    protected function errorExit(string $message): void
    {
        exit($message);
    }

}