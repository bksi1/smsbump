<?php

namespace app\models;

use app\core\Model;

/**
 * Class ValidationAttempts
 * @package app\models
 */
class ValidationAttempts extends Model
{
    public static function tableName(): string
    {
        return 'validation_attempts';
    }
    protected array $_attributes = [
        'id' => null,
        'user_id' => null,
        'is_valid' => false,
        'created_at' => null,
    ];
    public function beforeSave(bool $isNewRecord): void
    {
        if ($isNewRecord) {
            $this->_attributes['created_at'] = $this->created_at = (new \DateTime())->format('Y-m-d H:i:s');
        }
    }

    /**
     * @param mixed $userId
     * @return bool
     */
    public static function validAttempt(mixed $userId): bool
    {
        $attemptDate = (new \DateTime('-1 minute'))->format('Y-m-d H:i:s');
        $attempts = static::find(["user_id" => $userId])->andWhere(["!=", "is_valid", 1])->andWhere([">", "created_at", $attemptDate])->count();

        return $attempts < 3;
    }
}