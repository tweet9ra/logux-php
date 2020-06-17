<?php


namespace tweet9ra\Logux\exceptions;


class BruteForceException extends LoguxException
{
    protected $message = 'Too many wrong secret attempts';
    protected $code = 429;
}