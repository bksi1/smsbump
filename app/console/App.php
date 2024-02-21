<?php
namespace app\console;

use app\core\App as CoreApp;
use app\core\Controller;
use app\core\Route;
use app\console\controllers\BadRequestController;

/**
 * Class App for CLI usage
 * @package app\console
 */
class App extends CoreApp
{
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        global $argv;

        /**
         * we are using same uri schema as in the web app, we just put it as first argument after the script name
         */
        $curentRoute = $this->urlParser->parse($argv[1] ?? '');
        $controllerInstance = $this->createController($curentRoute);
        $controllerInstance->init();
        $action = 'action'. ucfirst($curentRoute->action);
        $controllerInstance->params = $curentRoute->params;
        $controllerInstance->handleRequest($action);
    }

    /**
     * @param Route $route
     * @return Controller
     */
    protected function createController(Route $route): Controller
    {
        try {
            $controllerName = 'app\\console\\controllers\\' . ucfirst($route->controller) . 'Controller';
            return new $controllerName;
        } catch (\Throwable $e) {
            return new BadRequestController();
        }
    }
}