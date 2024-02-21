<?php

namespace app\web\controllers;

use app\core\Container;
use app\models\OtpCode;
use app\models\User;
use app\models\ValidationAttempts;

/**
 * Class UserController
 * @package app\web\controllers
 */
class UserController extends Controller
{
    public function actionIndex($id = "")
    {
        $model = User::find(["id" => $id])->one();
        return ["user" => $model];
    }

    public function actionRegister()
    {
        $model = new User();
        $request = $this->request->post();

        $model->load($request);
        if (!$model->save()) {
            $this->triggerError(json_encode($model->getErrors()));
        }
        $model->afterRegister();
        return ["message" => "User registered, please check your phone for OTP code", "userId" => $model->id];
    }

    public function actionGenerate($id)
    {
        /** @var User $model */
        $model = User::find(["id" => $id])->one();
        if (!$model) {
            $this->triggerError("User not found", 404);
        }

        if (!OtpCode::attemptGenerate($model)) {
            $this->triggerError("Please wait a minute before generating a new code");
        }

        return ["message" => "Code generated, please check your phone"];
    }

    /**
     * @param $id
     * @return string[]
     * @throws \app\core\exceptions\InvalidConfigException
     */
    public function actionValidate($id): array
    {
        /** @var User $model */
        $model = User::find(["id" => $id])->one();
        if (!$model) {
            $this->triggerError("User not found", 404);
        }

        $code = $this->request->post("code");
        if (!$code) {
            $this->triggerError("Code is required");
        }
        $phone = "+".$model->phone ?? "unknown";
        $logMessage = "User width phone {$phone} has made attempt to validate the code {$code}";
        Container::getInstance()->get('log')->log($logMessage);

        if (!$model) {
            $this->triggerError("User not found", 404);
        }

        if ($model->validated) {
            $this->triggerError("User already validated");
        }

        if(!ValidationAttempts::validAttempt($model->id)) {
            $this->triggerError("Too many attempts, please wait a minute");
        }

        if (!OtpCode::validateCode($code, $model)) {
            $this->triggerError("Invalid code");
        }

        $model->validated = true;
        $model->save();

        return ["message" => "User validated successfully"];
    }

}