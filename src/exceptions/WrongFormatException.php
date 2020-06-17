<?php


namespace tweet9ra\Logux\exceptions;

class WrongFormatException extends LoguxException {
    protected $message = 'Wrong body';
    protected $code = 400;
}