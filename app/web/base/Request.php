<?php

namespace app\web\base;

/**
 * Class Request for web application
 * @package app\web\base
 */
class Request extends \app\core\Request
{
    /**
     * @param string|null $key
     * @param string|null $default
     * @return mixed
     */
    public function get(string $key = null, string $default = null): mixed
    {
        if (empty($key)) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * @param string|null $key
     * @param string|null $default
     * @return mixed
     */
    public function post(?string $key = null,?string $default = null): mixed
    {

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($key)) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    /**
     * @param string|null $key
     * @param string|null $default
     * @return mixed
     */
    public function input(?string $key,?string $default = null): mixed
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * @return bool
     */
    public function isJson(): bool
    {
        return strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /**
     * @return bool
     */
    public function isXml(): bool
    {
        return strpos($_SERVER['HTTP_ACCEPT'], 'application/xml') !== false;
    }

    /**
     * @return bool
     */
    public function isHtml(): bool
    {
        return strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false;
    }

}