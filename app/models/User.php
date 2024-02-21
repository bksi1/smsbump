<?php
namespace app\models;

use app\core\Model;

/**
 * Class User
 * @package app\models
 */
class User extends Model
{
    public ?int $id;
    public ?string $password;
    public ?string $email;
    public ?bool $validated;
    public ?string $created_at;
    protected array $_attributes = ['id' => null, 'phone' => null, 'email' => null, 'validated' => false, 'password' => null, 'created_at'=> null];

    /**
     * @return string
     */
    public static function tableName(): string
    {
        return 'user';
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'email' => [
                'validators' => ['required', 'email', 'unique'],
                'formatters' => ['trim', 'strtolower']
            ],
            'phone' => [
                'validators' => ['required', 'phone', 'unique'],
                'formatters' => ['trim', 'phone']
            ],
            'password' => [
                'validators' => ['required', 'password'],
                'formatters' => ['trim']
            ]
        ];
    }

    /**
     * @return void
     */
    public function beforeSave(bool $isNewRecord): void
    {
        if ($isNewRecord) {
            $this->_attributes['validated'] = $this->validated = false;
            $this->_attributes['created_at'] = $this->created_at = (new \DateTime())->format('Y-m-d H:i:s');
            $this->generatePasswordHash();
        }
    }

    /**
     * @return void
     */
    public function afterRegister() : void
    {
        OtpCode::attemptGenerate($this, true);
    }

    /**
     * @return void
     */
    private function generatePasswordHash(): void
    {
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
    }
}