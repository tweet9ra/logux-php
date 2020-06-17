<?php


namespace tweet9ra\Logux\exceptions;


class NotSupportedProtocolException extends LoguxException {
    protected $message = 'Back-end protocol version is not supported';
    protected $code = 400;
}