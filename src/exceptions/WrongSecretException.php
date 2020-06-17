<?php


namespace tweet9ra\Logux\exceptions;

class WrongSecretException extends LoguxException {
    protected $message = 'Wrong secret';
    protected $code = 403;
}