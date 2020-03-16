<?php


namespace tweet9ra\Logux;


class EventsHandler implements EventsHandlerInterface
{
    // Before process all actions command, except auth
    public const BEFORE_PROCESS_ACTION = 0;
    // Some action got error, except auth
    public const ACTION_ERROR = 1;
    // Auth action got error
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