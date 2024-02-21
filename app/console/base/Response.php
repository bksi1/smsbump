<?php

namespace app\console\base;

use app\core\Response as BaseResponse;

/**
 * Class Response for console application
 * @package app\console\base
 */
class Response extends BaseResponse
{
    public $content;
    public function send(): void
    {
        echo $this->content;
    }

    /**
     * Sets response body
     * @param mixed $data
     */
    public function setData(mixed $data): void
    {
        $this->content = $data;
    }

    /**
     * @param int $code
     */
    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    /**
     * @param string $message
     * @param int $code
     */
    public function setError(string $message,int $code): void
    {
        $this->setStatusCode($code);
        $this->setData($message);
    }
}