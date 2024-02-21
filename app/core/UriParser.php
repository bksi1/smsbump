<?php

namespace app\core;

/**
 * Class UriParser
 * @package app\core
 */
class UriParser
{
    /**
     * @param string $route
     * @return Route
     */
    public function parse(string $uri) : Route
    {
        $uriArray = $this->getUriArray($uri);
        $route = new Route();
        if (isset($uriArray[0])) {
            $route->controller = $uriArray[0];
            unset($uriArray[0]);
        }
        if (isset($uriArray[1])) {
            $route->action = $uriArray[1];
            unset($uriArray[1]);
        }
        $route->params = array_values($uriArray);
        return $route;
    }

    /**
     * @param string $uri
     * @return array
     */
    public function getUriArray(string $uri) : array
    {
        if (empty($uri)) {
            return [];
        }
        return explode('/', filter_var(rtrim($uri, '/'), FILTER_SANITIZE_URL));
    }

}