<?php

namespace app\core\interfaces;

interface ArrayableInterface
{
    public function fields();
    public function extraFields();

    /**
     * Converts the object into an array.
     *
     * @param array $fields the fields that the output array should contain. Fields not specified
     * @param array $expand the additional fields that the output array should contain.
     * @param bool $recursive whether to recursively return array representation of embedded objects.
     * @return array the array representation of the object
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true);

}