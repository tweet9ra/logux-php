<?php

namespace tweet9ra\Logux;

/**
 * Dispatcher that stores actions instead of sending to Logux
 * Useful for testing and debugging
 */
class StackActionsDispatcher implements ActionsDispatcherInterface
{
    protected $dispatchedActions = [];

    public function dispatch(array $commands)
    {
        $this->dispatchedActions = array_merge($this->dispatchedActions, $commands['commands'] ?? []);
    }

    public function getDispatchedActions() : array
    {
        return $this->dispatchedActions;
    }

    public function search(string $type)
    {
        return array_filter($this->dispatchedActions, function ($action) use ($type) {
            return isset($action[1]['type']) && $action[1]['type'] === $type;
        });
    }
}