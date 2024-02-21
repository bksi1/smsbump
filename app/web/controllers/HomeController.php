<?php

namespace app\web\controllers;

/**
 * Class HomeController - this is mockup controller for API
 * @package app\web\controllers
 */
class HomeController extends Controller
{
    public function actionIndex()
    {
        return ["message" => "SMS API is working!"];
    }
}