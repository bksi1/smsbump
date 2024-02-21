<?php

namespace app\console\controllers;

class BadRequestController extends Controller
{
    public function actionIndex()
    {
        return "Bad/missing command or action\n";
    }
}