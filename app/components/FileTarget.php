<?php
namespace app\components;

use app\core\ConfigHelper;
use app\core\interfaces\LogInterface;

/**
 * Class FileTarget
 * @package app\components
 */
class FileTarget implements LogInterface
{
    private $logFileName;
    private $logPath = 'logs/';
    private $config;
    public function __construct(string $logFileName)
    {
        $this->logFileName = $logFileName;
        $this->config = ConfigHelper::getInstance();
        $baseAppPath = $this->config->get('aliases')['@app'];
        $this->logPath = $baseAppPath . '/'.$this->logPath;
    }
    public function log(string $message)
    {
        file_put_contents($this->logPath.$this->logFileName, $this->prepareMessage($message), FILE_APPEND);
    }

    private function prepareMessage($message)
    {
        return date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
    }
}