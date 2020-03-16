<?php


namespace tweet9ra\Logux;


interface EventsHandlerInterface
{
    public function setEvent(int $eventType, $event);

    public function addEvent($eventType, \Closure $callback);

    public function fire($eventType, ...$params);
}