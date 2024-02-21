<?php

namespace app\web\controllers;

use app\core\Container;
use app\web\base\JsonResponseFormatter;
use app\web\base\Request;
use app\web\base\Response;
use app\core\Controller as BaseController;

/**
 * Class Controller
 * @package app\web\controllers
 * @property Request $request
 * @property Response $response
 */
class Controller extends BaseController
{
    protected Request $request;
    protected Response $response;

    /**
     * @return void
     */
    public function init(): void
    {
        $this->response = new Response();
        $this->response->setFormatter(new JsonResponseFormatter());  // should implement Strategy/Factory pattern if we want to use for both API/web with views
    }

    /**
     * @param string $action
     * @return void
     */
    public function handleRequest(string $action): void
    {
        $this->request = new Request();
        if ($this->request->method() === 'OPTIONS') {
            $this->response->send();
            exit;
        }
        try {
            if (! method_exists($this, $action)) {
                throw new \Exception("Not found");
            }


            $result = call_user_func_array([$this, $action], $this->params);
            $this->response->setData($result);
            $this->response->send();
        } catch (\Throwable $e) {
            Container::getInstance()->get('log')->log($e->getMessage());
            $this->triggerError($e->getMessage(), 404);
        }
    }

    /**
     * @param string $message
     * @param int $code
     */
    protected function triggerError(string $message,int $code = 400): void
    {
        $this->response->setError($message, $code);
        $this->response->send();
        exit;
    }
}