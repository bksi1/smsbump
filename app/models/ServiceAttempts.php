<?php

namespace app\models;

use app\core\Model;

/**
 * Class ServiceAttempts
 * @package app\models
 */
class ServiceAttempts extends Model
{
    public $id;
    public $queue_id;
    public $result;
    public $created_at;

    protected array $_attributes = ['id' => null, 'queue_id' => null, 'result' => '', 'created_at' => null];

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNewRecord): void
    {
        if ($isNewRecord) {
            $this->_attributes['created_at'] = $this->created_at = (new \DateTime())->format('Y-m-d H:i:s');
        }
    }

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return 'service_attempts';
    }

    /**
     * @param ServiceQueue $serviceWorker
     * @param string $result
     * @return ServiceAttempts
     */
    public static function createAttempt(ServiceQueue $serviceWorker, string $result): ServiceAttempts
    {
        $attempt = new ServiceAttempts();
        $attempt->queue_id = $serviceWorker->id;
        $attempt->result = $result;
        $attempt->save();
        return $attempt;
    }
}