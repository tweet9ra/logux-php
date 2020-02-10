<?php


namespace tweet9ra\Logux;


interface ActionsDispatcherInterface
{
    public function dispatch(array $commands);
}