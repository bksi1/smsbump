<?php

namespace app\web\base;

use app\web\base\exceptions\HeadersAlreadySentException;
use InvalidArgumentException;
use app\core\Response as BaseResponse;

/**
 * Class Response
 * @package app\web\base
 * @property mixed $_statusCode
 * @property mixed $version
 * @property mixed $httpStatuses
 * @property mixed $charset
 * @property mixed $statusText
 * @property mixed $isSent
 * @property JsonResponseFormatter $_formatter
 * @property mixed $data
 * @property mixed $content
 * @property mixed $stream
 * @property mixed $_headers
 */
class Response extends BaseResponse
{
    private $_statusCode = 200;
    public $version;
    public static $httpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];
    public $charset;
    public $statusText;
    public $isSent = false;
    private JsonResponseFormatter $_formatter;
    public $data;
    public $content;
    public $stream;
    public $_headers;

    /**
     * @return void
     */
    public function init()
    {
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }
        if ($this->charset === null) {
            $this->charset = 'UTF-8';
        }
    }

    /**
     * @param int $value
     * @param string|null $text
     * @return $this
     */
    public function setStatusCode(int $value,?string $text = null): Response
    {
        if ($value === null) {
            $value = 200;
        }
        $this->_statusCode = (int) $value;
        if ($this->_statusCode < 100 || $this->_statusCode >= 600) {
            throw new InvalidArgumentException("The HTTP status code is invalid: $value");
        }
        if ($text === null) {
            $this->statusText = isset(static::$httpStatuses[$this->_statusCode]) ? static::$httpStatuses[$this->_statusCode] : '';
        } else {
            $this->statusText = $text;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->_statusCode;
    }

    /**
     * @return HeadersCollection
     */
    public function getHeaders(): HeadersCollection
    {
        if ($this->_headers === null) {
            $this->_headers = new HeadersCollection();
        }

        return $this->_headers;
    }

    /**
     * @return void
     */
    public function send(): void
    {
        if ($this->isSent) {
            return;
        }

        $this->prepare();
        $this->sendHeaders();
        $this->sendContent();
        $this->isSent = true;
    }

    /**
     * @return void
     */
    protected function sendContent(): void
    {
        if ($this->stream === null) {
            echo $this->content;

            return;
        }

        if (is_callable($this->stream)) {
            $data = call_user_func($this->stream);
            foreach ($data as $datum) {
                echo $datum;
                flush();
            }
            return;
        }

        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list($handle, $begin, $end) = $this->stream;

            // only seek if stream is seekable
            if ($this->isSeekable($handle)) {
                fseek($handle, $begin);
            }

            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
            fclose($this->stream);
        }
    }

    /**
     * @return void
     * @throws HeadersAlreadySentException
     */
    protected function sendHeaders(): void
    {
        if (headers_sent($file, $line)) {
            throw new HeadersAlreadySentException($file, $line);
        }
        if ($this->_headers) {
            foreach ($this->getHeaders() as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
                foreach ($values as $value) {
                    header("$name: $value", $replace);
                    $replace = false;
                }
            }
        }
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} {$statusCode} {$this->statusText}");

    }

    /**
     * @return void
     */
    protected function prepare(): void
    {
        if (in_array($this->getStatusCode(), [204, 304])) {
            // A 204/304 response cannot contain a message body according to rfc7231/rfc7232
            $this->content = '';
            $this->stream = null;
            return;
        }

        if ($this->stream !== null) {
            return;
        }

        $this->_formatter->format($this);

        if (is_array($this->content)) {
            $this->content = json_encode($this->content);
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidArgumentException('Response content must be a string or an object implementing __toString().');
            }
        }
    }

    /**
     * @param mixed $formatter
     * @return void
     */
    public function setFormatter(mixed $formatter): void
    {
        $this->_formatter = $formatter;
    }

    /**
     * @param mixed $handle
     * @return bool
     */
    private function isSeekable(mixed $handle): bool
    {
        if (!is_resource($handle)) {
            return true;
        }

        $metaData = stream_get_meta_data($handle);
        return isset($metaData['seekable']) && $metaData['seekable'] === true;
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData(mixed $data): void
    {
        $this->data = $data;
    }

    /**
     * @param string $message
     * @param int $code
     * @return void
     */
    public function setError(string $message,int $code): void
    {
        $this->setStatusCode($code);
        $this->content = ["error" =>$message, "code" => $code];
    }

}