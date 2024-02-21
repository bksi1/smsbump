<?php

namespace app\console\controllers;

use app\core\Controller as BaseController;
use app\console\base\Request;
use app\console\base\Response;

/**
 * Class Controller
 * @package app\console\controllers
 */
class Controller extends BaseController
{

    public function init(): void
    {
        $this->response = new Response();
    }

    /**
     * @param string $action
     * @return void
     */
    public function handleRequest(string $action): void
    {
        try {
            if (! method_exists($this, $action)) {
                throw new \Exception("Method does not exist");
            }

            $this->request = new Request();
            $result = call_user_func_array([$this, $action], $this->params);
            $this->response->setData($result);
            $this->response->send();
            exit(0);
        } catch (\Throwable $e) {
            $this->triggerError("Bad request: ".$e->getMessage());
        }
        exit(4);
    }

    /**
     * @param string $message
     * @param int $code
     */
    protected function triggerError($message, $code = 4): void
    {
        $this->response->setError($message, $code);
        $this->response->send();
        exit($code);
    }
}