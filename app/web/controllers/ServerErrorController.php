<?php

namespace app\web\controllers;

/**
 * Class ServerErrorController
 * @package app\web\controllers
 *
 * Default 500 controller
 */
class ServerErrorController extends Controller
{
    public function actionIndex()
    {
        $this->response->setStatusCode(500);
        return ['message' => 'Server error'];
    }

}