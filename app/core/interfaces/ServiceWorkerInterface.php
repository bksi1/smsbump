<?php
namespace app\core\interfaces;

/**
 * ServiceWorkerInterface interface file.
 * Interface for service workers.
 */
interface ServiceWorkerInterface
{
    public function process(array $params);
}