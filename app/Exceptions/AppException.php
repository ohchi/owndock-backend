<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppException extends HttpException
{
    protected $data;

    public function __construct(int $code = null, $data = null, array $headers = [], $msg = null)
    {
        $this->data = $data;
        $httpStatusCode = 500;
        $message = config('errors.default.message');
        $error = config("errors.$code");

        if ($error) {

            $message = $error['message'];
            $httpStatusCode = $error['http_status_code'];
    
        } elseif ($code >= 100 && $code < 600) {

            $httpStatusCode = $code;
        }

        if ($msg) $message = $msg;

        parent::__construct($httpStatusCode, $message, null, $headers, $code);
    }

    public function getResponseBody()
    {
        $body = [
            'error' => [
                'code' => $this->code,
                'message' => $this->message
            ]
        ];
        
        if ($this->data) {
            $body['error']['data'] = $this->data;
        }
        
        return $body;
    }
}
