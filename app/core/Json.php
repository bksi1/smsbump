<?php

namespace app\core;

use app\core\interfaces\ArrayableInterface;
use InvalidArgumentException;

/**
 * Class Json
 * @package app\core
 */
class Json
{
    public static $prettyPrint;
    public static $keepObjectType = false;
    public static $jsonErrorMessages = [
        'JSON_ERROR_SYNTAX' => 'Syntax error',
        'JSON_ERROR_UNSUPPORTED_TYPE' => 'Type is not supported',
        'JSON_ERROR_DEPTH' => 'The maximum stack depth has been exceeded',
        'JSON_ERROR_STATE_MISMATCH' => 'Invalid or malformed JSON',
        'JSON_ERROR_CTRL_CHAR' => 'Control character error, possibly incorrectly encoded',
        'JSON_ERROR_UTF8' => 'Malformed UTF-8 characters, possibly incorrectly encoded',
    ];

    /**
     * @param mixed $value the data to be encoded.
     * @param int $options the encoding options. For more details please refer to
     * @return string the encoding result.
     * @throws InvalidArgumentException if there is any encoding error.
     */
    public static function encode(mixed $value,int $options = 320): string
    {
        $expressions = [];
        $value = static::processData($value, $expressions, uniqid('', true));
        set_error_handler(function () {
            static::handleJsonError(JSON_ERROR_SYNTAX);
        }, E_WARNING);

        if (static::$prettyPrint === true) {
            $options |= JSON_PRETTY_PRINT;
        } elseif (static::$prettyPrint === false) {
            $options &= ~JSON_PRETTY_PRINT;
        }

        $json = json_encode($value, $options);
        restore_error_handler();
        static::handleJsonError(json_last_error());

        return $expressions === [] ? $json : strtr($json, $expressions);
    }

    /**
     * @param mixed $value the data to be encoded
     * @return string the encoding result
     * @throws InvalidArgumentException if there is any encoding error
     */
    public static function htmlEncode(mixed $value): string
    {
        return static::encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * @param string $json the JSON string to be decoded
     * @param bool $asArray whether to return objects in terms of associative arrays.
     * @return mixed the PHP data
     * @throws InvalidArgumentException if there is any decoding error
     */
    public static function decode(string $json,bool $asArray = true): mixed
    {
        if (is_array($json)) {
            throw new InvalidArgumentException('Invalid JSON data.');
        } elseif ($json === null || $json === '') {
            return null;
        }
        $decode = json_decode((string) $json, $asArray);
        static::handleJsonError(json_last_error());

        return $decode;
    }

    /**
     * @param int $lastError error code from [json_last_error()]
     * @throws InvalidArgumentException if there is any encoding/decoding error.
     */
    protected static function handleJsonError(int $lastError): void
    {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }

        if (PHP_VERSION_ID >= 50500) {
            throw new InvalidArgumentException(json_last_error_msg(), $lastError);
        }

        foreach (static::$jsonErrorMessages as $const => $message) {
            if (defined($const) && constant($const) === $lastError) {
                throw new InvalidArgumentException($message, $lastError);
            }
        }

        throw new InvalidArgumentException('Unknown JSON encoding/decoding error.');
    }

    /**
     * @param mixed $data the data to be processed
     * @param array $expressions collection of JavaScript expressions
     * @param string $expPrefix a prefix internally used to handle JS expressions
     * @return mixed the processed data
     */
    protected static function processData(mixed $data,array &$expressions,string $expPrefix): mixed
    {
        $revertToObject = false;

        if (is_object($data)) {
            if ($data instanceof \JsonSerializable) {
                return static::processData($data->jsonSerialize(), $expressions, $expPrefix);
            }

            if ($data instanceof \DateTimeInterface) {
                return static::processData((array)$data, $expressions, $expPrefix);
            }

            if ($data instanceof ArrayableInterface) {
                $data = $data->toArray();
            } elseif ($data instanceof \SimpleXMLElement) {
                $data = (array) $data;
                $revertToObject = true;
            } else {
                $revertToObject = static::$keepObjectType;

                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;

                if ($data === []) {
                    $revertToObject = true;
                }
            }
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = static::processData($value, $expressions, $expPrefix);
                }
            }
        }

        return $revertToObject ? (object) $data : $data;
    }
}