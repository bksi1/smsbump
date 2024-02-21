<?php

namespace app\core;

/**
 * Class Route
 * Used for sending object with typical parameters instead of array to controller
 *
 * @package app\core
 */
class Route
{
    public $controller = 'site';
    public $action = 'index';
    public $params = [];
}