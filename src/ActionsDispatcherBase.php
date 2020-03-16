<?php


namespace tweet9ra\Logux;


abstract class ActionsDispatcherBase
{
    protected $controlUrl;

    public function __construct(string $controlUrl = null)
    {
        $this->controlUrl = $controlUrl;
    }

    abstract public function dispatch(array $commands);
}