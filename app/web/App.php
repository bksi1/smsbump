<?php

namespace app\web;

use app\core\App as CoreApp;
use app\core\Controller;
use app\core\Route;
use app\web\base\JsonResponseFormatter;
use app\web\base\Response;
use app\web\controllers\NotFoundController;

/**
 * Class App
 * @package app\web
 *
 * This is the main application class for web application
 *
 */
class App extends CoreApp
{

    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * @return void
     */
    public function run() : void
    {
        /**
         * _GET['url'] is set via .htaccss
         */
        $curentRoute = $this->urlParser->parse($_GET['url'] ?? '');
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
    public function createController(Route $route) : Controller
    {
        try {
            $controllerName = 'app\\web\\controllers\\' . ucfirst($route->controller) . 'Controller';
            return new $controllerName;
        } catch (\Throwable $e) {
            return new NotFoundController();
        }
    }

    /**
     * @param string $message
     * @return void
     */
    public function errorExit(string $message): void
    {
        $response = new Response();
        $response->setFormatter(new JsonResponseFormatter());
        $response->setStatusCode(500);
        $response->data = ['error' => $message];
        $response->send();
        exit;
    }
}