<?php


namespace tweet9ra\Logux;


class EventsHandler
{
    public const BEFORE_PROCESS_ACTION = 0;
    public const ACTION_ERROR = 1;
    public const AUTH_ACTION_ERROR = 2;

    protected $eventsMap;

    /**
     * @param int $eventType
     * @param \Closure|\Closure[] $event
     */
    public function setEvent(int $eventType, $event)
    {
        $this->eventsMap[$eventType] = is_array($event) ? $event : [$event];
    }

    public function addEvent($eventType, \Closure $callback)
    {
        $this->eventsMap[$eventType][] = $callback;
    }

    public function fire($eventType, ...$params)
    {
        if (isset($this->eventsMap[$eventType]) && is_array($this->eventsMap[$eventType])) {
            foreach ($this->eventsMap[$eventType] as $callback) {
                call_user_func_array($callback, $params);
            }
        }
    }
}