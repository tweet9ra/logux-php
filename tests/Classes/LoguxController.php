<?php

namespace Tests\Classes;

use tweet9ra\Logux\ProcessableAction;

class LoguxController
{
    public function subscribeToChannel(ProcessableAction $action, $arg1, $arg2)
    {
        if ($arg1 !== 'arg1value' || $arg2 !== 'arg2value') {
            throw new \Exception("Invalid arg value $arg1;$arg2");
        }
    }

    public function processActionAndResend(ProcessableAction $action)
    {
        $action->sendTo('channels', 'channel/1337');
    }
}