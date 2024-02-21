<?php

namespace app\models;

use app\core\Model;

/**
 * Class OtpCode
 * @package app\models
 */
class OtpCode extends Model
{
    public ?int $id = null;
    public ?string $code = null;
    public int $user_id;

    public ?string $created_at = null;

    protected array $_attributes = ['id' => null, 'code' => null, 'user_id' => null, 'created_at' => null];

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'otp_codes';
    }

    /**
     * @return void
     */
    public function beforeSave(bool $isNewRecord): void
    {
        if ($isNewRecord) {
            $this->generateCode();
            $this->_attributes['created_at'] = $this->created_at = (new \DateTime())->format('Y-m-d H:i:s');
        }
    }

    /**
     * @return void
     */
    private function generateCode(): void
    {
        $this->_attributes['code'] = $this->code = rand(100000, 999999);
    }

    /**
     * @param string|null $code
     * @param int $userId
     * @return bool
     */
    public static function validateCode(?string $code, User $user): bool
    {
        $validationAtttempt = new ValidationAttempts();
        $validationAtttempt->user_id = $user->id;;
        $validationAtttempt->is_valid = false;

        $model = static::find(["user_id" => $user->id])->andWhere(["=", "code", $code])->orderBy(["id" => SORT_DESC])->one();
        if (!$model) {
            $validationAtttempt->save();
            return false;
        }

        /** @var OtpCode $model */
        if ($model->code === $code) {
            $validationAtttempt->is_valid = true;
            ServiceQueue::enqueueJob("app\\services\\SmsServiceWorker", ["message" => "Welcome to SMSBump!", "phone" => $user->phone]);
        }

        $validationAtttempt->save();
        return $validationAtttempt->is_valid;
    }

    /**
     * @param User $user
     * @return bool
     */
    public static function attemptGenerate(User $user,bool $isNewUser = false): bool
    {
        $pastDate = (new \DateTime('-1 minute'))->format('Y-m-d H:i:s');

        if (!$isNewUser)
            $codeModel = self::find(["user_id" => $user->id])->andWhere([">", 'created_at', $pastDate])->orderBy(["id" => SORT_DESC])->one();

        if (empty($codeModel)) {
            $codeModel = new OtpCode();
            $codeModel->user_id = $user->id;
            if ($codeModel->save()) {
                ServiceQueue::enqueueJob("app\\services\\SmsServiceWorker", ["message" => "Please validate this OTP code ".$codeModel->code, "phone" => $user->phone]);
                return true;
            }
        }
        return false;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        /** @var User $model */
        $model = User::find(["id" => $this->user_id])->one();
        return $model;
    }
}
