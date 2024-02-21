<?php

namespace app\web\base;

interface ResponseFormatterInterface
{
    /**
     * @param Response $response
     * @return void
     */
    public function format(Response $response): void;

}