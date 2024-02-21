<?php

namespace app\core;

/**
 * Class Controller used as base controller for both web and cli applications
 * @package app\core
 * @property array $params
 */
abstract class Controller
{
    public $params = [];

    public function __construct()
    {
        $this->init();
    }

    /**
     * @return void
     */
    public abstract function init(): void;

    /**
     * @param string $action
     * @return void
     */
    public function handleRequest(string $action): void
    {
        try {
            call_user_func_array([$this, $action], $this->params);
        } catch (\Exception $e) {
            exit("Bad request: ".$e->getMessage());
        }
    }
}