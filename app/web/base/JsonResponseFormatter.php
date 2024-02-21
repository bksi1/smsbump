<?php

namespace app\web\base;

use app\core\Json;

/**
 * Class JsonResponseFormatter
 * @package app\web\base
 *
 * @property string $contentType
 * @property bool $useJsonp
 * @property int $encodeOptions
 * @property bool $prettyPrint
 * @property bool $keepObjectType
 *
 */
class JsonResponseFormatter implements ResponseFormatterInterface
{
    const CONTENT_TYPE_JSONP = 'application/javascript; charset=UTF-8';
    const CONTENT_TYPE_JSON = 'application/json; charset=UTF-8';

    public string $contentType = self::CONTENT_TYPE_JSON;
    public bool $useJsonp = false;
    public int $encodeOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    public bool $prettyPrint = false;
    public bool $keepObjectType = true;

    public static function create($config = []): self
    {
        $formatter = new JsonResponseFormatter();
        foreach ($config as $key => $value) {
            if (isset($formatter->$key))
                $formatter->$key = $value;
        }
        return $formatter;
    }

    /**
     * @param Response $response
     */
    public function format($response): void
    {
        if ($this->contentType === null) {
            $this->contentType = $this->useJsonp
                ? self::CONTENT_TYPE_JSONP
                : self::CONTENT_TYPE_JSON;
        } elseif (strpos($this->contentType, 'charset') === false) {
            $this->contentType .= '; charset=UTF-8';
        }
        $response->getHeaders()->set('Content-Type', $this->contentType);

        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }

    /**
     * Formats response data in JSON format.
     * @param Response $response
     */
    protected function formatJson($response): void
    {
        if ($response->data !== null) {
            $options = $this->encodeOptions;
            if ($this->prettyPrint) {
                $options |= JSON_PRETTY_PRINT;
            }

            $default = Json::$keepObjectType;
            if ($this->keepObjectType !== null) {
                Json::$keepObjectType = $this->keepObjectType;
            }

            $response->content = Json::encode($response->data, $options);

            // Restore default value to avoid any unexpected behaviour
            Json::$keepObjectType = $default;
        } elseif ($response->content === null) {
            $response->content = 'null';
        }
    }

    /**
     * Formats response data in JSONP format.
     * @param Response $response
     */
    protected function formatJsonp($response): void
    {
        if (is_array($response->data)
            && isset($response->data['data'], $response->data['callback'])
        ) {
            $response->content = sprintf(
                '%s(%s);',
                $response->data['callback'],
                Json::htmlEncode($response->data['data'])
            );
        } elseif ($response->data !== null) {
            $response->content = '';
        }
    }
}