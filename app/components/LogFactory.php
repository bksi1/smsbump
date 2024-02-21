<?php
namespace app\components;

use app\core\interfaces\LogInterface;

/**
 * Class LogComponent
 */
class LogFactory
{
    private string $targetClass;
    private $logFileName;


    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->targetClass = $config['targetClass'];
        $this->logFileName = $config['logFileName'];
    }

    /**
     * @param string $message
     */
    public function log($message): void
    {
        $target = new $this->targetClass($this->logFileName);
        $target->log($message);
    }

}