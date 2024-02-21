<?php

namespace app\console\controllers;

use app\core\ConfigHelper;
use app\core\Container;
use app\models\ServiceQueue;
use Throwable;

class CommandController extends Controller
{

    public function actionIndex()
    {
        return "Hello, World!";
    }

    public function actionInit()
    {
        print_r("Are you sure you want to init the database? This will dropp and recreate all current tables. (yes/no): ");
        $answer = readline();

        if (trim(strtolower($answer)) !== 'yes' && trim(strtolower($answer)) !== 'y') {
            return;
        }

        print_r("Initializing database...\n");
        try {
            $db = Container::getInstance()->get('db');
            $config = ConfigHelper::getInstance();
            $initSql = file_get_contents($config->getAlias('@app') . '/init/init.sql');
            $db->createCommand($initSql)->execute();
            print_r("Database initialized.\n");
        } catch (Throwable $e) {
            $this->triggerError("Error: " . $e->getMessage() . "\n");
        }
        return "All set\n";
    }

    public function actionSend()
    {
        $smsWorkers = ServiceQueue::find(['status' => ServiceQueue::STATUS_NEW])->all();
        foreach ($smsWorkers as $smsWorker) {
            /* @var $smsWorker ServiceQueue */
            $smsWorker->processWorker();
        }
    }

}