<?php
namespace app\services;
use app\core\interfaces\ServiceWorkerInterface;
use app\models\SmsMock;

/**
 * SmsService class file.
 * Mock service for sending SMS.
 */
class SmsServiceWorker implements ServiceWorkerInterface
{
    /**
     * @param $phone
     * @param $message
     * @return bool
     */
    public function sendSms($phone, $message): bool
    {
        // Mock sending SMS
        $smsMock = new SmsMock();
        $smsMock->phone = $phone;
        $smsMock->message = $message;
        return $smsMock->save();
    }

    /**
     * @param array $params
     * @return bool
     */
    public function process(array $params) : bool
    {
        $phone = $params['phone'];
        $message = $params['message'];
        return $this->sendSms($phone, $message);
    }
}