<?php

require __DIR__ . '/../vendor/autoload.php';

use app\web\App;

$config = require __DIR__ . '/../app/config/config.php';

$app = new App($config);

$app->run();