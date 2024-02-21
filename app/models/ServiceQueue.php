<?php

namespace app\models;

use app\core\interfaces\ServiceWorkerInterface;
use app\core\Json;
use app\core\Model;
use DateTime;

/**
 * Class ServiceQueue
 * @package app\models
 */
class ServiceQueue extends Model
{
    public const STATUS_NEW = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_DONE = 2;
    public const STATUS_ERROR = 3;

    public ?int $id = null;
    public string $service;
    public int $status = self::STATUS_NEW;
    public string $params;
    public string $created_at;
    public string $last_attempt;
    public int $count_retries = 0;

    protected array $_attributes = ['id' => null, 'service' => '', 'status' => self::STATUS_NEW, 'params' => '', 'created_at' => '', 'last_attempt' => '', 'count_retries' => 0];

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNewRecord): void
    {
        if ($isNewRecord) {
            $this->_attributes['created_at'] = $this->created_at = (new DateTime())->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'service_queue';
    }

    /**
     * @return string
     */
    public static function primaryKey(): string
    {
        return 'id';
    }

    /**
     * @return void
     */
    public function processWorker(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->last_attempt = (new DateTime())->format('Y-m-d H:i:s');
        $this->count_retries++;
        $this->save();

        $workerClass = $this->service;
        $worker = new $workerClass();
        $params = Json::decode($this->params, true);

        $resultMessage = '';
        $result = false;
        try {
            if (!($worker instanceof ServiceWorkerInterface)) {
                throw new \Exception('Invalid worker class');
            }
            $result = $worker->process($params);
            $resultMessage = 'Success';
        } catch (\Exception $e) {
            $resultMessage = $e->getMessage();
        } finally {
            ServiceAttempts::createAttempt($this, $resultMessage);
            // if the worker returns true, the job is done
            if ($result) {
                $this->status = self::STATUS_DONE;
            } else if($this->count_retries > 5) {
                $this->status = self::STATUS_ERROR;
            } else {
                $this->status = self::STATUS_NEW;
            }
            $this->save();
        }
    }

    /**
     * @param string $service
     * @param array $params
     * @return void
     */
    public static function enqueueJob(string $service, array $params): void
    {
        $job = new ServiceQueue();
        $job->service = $service;
        $job->status = self::STATUS_NEW;
        $job->last_attempt = (new DateTime())->format('Y-m-d H:i:s');
        $job->params = Json::encode($params);
        $job->save();
    }
}