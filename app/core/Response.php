<?php

namespace app\core;

/**
 * Base response class for both console and web applications.
 *
 */
abstract class Response
{
    protected $content;

    /**
     * Sends the response back to the client
     * @param mixed $content
     */
    public abstract function send(): void;

    /**
     * Sets response body
     * @param mixed $data
     */
    public abstract function setData(mixed $data): void;

    /**
     * @param string $message
     * @param int $code
     */
    public abstract function setError(string $message,int $code): void;

}