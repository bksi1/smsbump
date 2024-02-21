<?php

namespace app\web\controllers;

/**
 * Class NotFoundController
 * @package app\web\controllers
 *
 * Default 404 controller
 */
class NotFoundController extends Controller
{
    public function actionIndex()
    {
        $this->response->setStatusCode(404);
        return ['message' => 'Not found'];
    }

}