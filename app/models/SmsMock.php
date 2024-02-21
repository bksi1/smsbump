<?php

namespace app\models;

use app\core\Model;
use DateTime;

/**
 * Class SmsMock
 * @package app\models
 */
class SmsMock extends Model
{
    public int $id;
    public string $phone;
    public string $message;
    public $created_at;

    public static function tableName(): string
    {
        return 'sms_mock';
    }

    protected static $_attributes = [
        'id' => null,
        'phone' => null,
        'message' => null,
        'created_at' => null,
    ];

    /**
     * @inheritDoc
     */
    public function beforeSave(bool $isNewRecord): void
    {
        if ($isNewRecord) {
            $this->_attributes['created_at'] = $this->created_at = (new DateTime())->format('Y-m-d H:i:s');
        }
    }


}