<?php

namespace tweet9ra\Logux;

class CommandsProcessor
{
    protected $actionsMap;

    public function setActionsMap(array $actionsMap)
    {
        $this->actionsMap = $actionsMap;
    }

    public function processCommand(array $command) : array
    {
        // Processing auth
        if ($command[0] === 'auth') {
            try {
                $authResponse = $this->processAuthCommand($command);
            } catch (\Exception $e) {
                App::getInstance()->getEventsHandler()
                    ->fire(EventsHandler::AUTH_ACTION_ERROR, $e, $command);
                $authResponse =  ['error', $e->getMessage()];
            }

            return [$authResponse];
        }

        try {
            $actionResponse = $this->processActionCommand($command);
        } catch (\Exception $e) {
            // Handle callback internal errors or bad logic
            App::getEventsHandler()->fire(EventsHandler::ACTION_ERROR, $e, $command);
            $actionResponse = [['error', $e->getMessage()]];
        }

        return $actionResponse;
    }

    protected function processAuthCommand(array $command)
    {
        if (!isset($this->actionsMap['auth'])) {
            throw new \LogicException('Auth handler is not specified');
        }

        $authResult = $this->callAction(
            $this->actionsMap['auth'],
            $command[3],
            $command[1],
            $command[2]
        );

        return [
            $authResult ? 'authenticated' : 'denied',
            $command[3]
        ] ;
    }

    protected function processActionCommand(array $command)
    {
        $actionType = $command[1]['type'];
        if (!isset($this->actionsMap[$actionType])) {
            return ['unknownAction', $command[2]['id']];
        }

        $params = [];
        if (
            $actionType === BaseAction::TYPE_SUBSCRIBE
            && is_array($this->actionsMap[BaseAction::TYPE_SUBSCRIBE])
        ) {
            // Processing subscription action if subscription map defined as array
            [$callback, $params] = $this->matchSubscriptionChannel($command[1]['channel']);
            if (!$callback) {
                return ['unknownChannel', $command[2]['id']];
            }
        } else {
            $callback = $this->actionsMap[$actionType];
        }

        $action = $this->createProcessableActionForCallback($callback, $command);

        App::getEventsHandler()->fire(EventsHandler::BEFORE_PROCESS_ACTION, $action);

        array_unshift($params, $action);

        $this->callAction($callback, ...$params);

        if (!$action->getLog()) {
            $action->approved()->processed();
        }

        return $action->toLoguxResponse();
    }

    public function matchSubscriptionChannel(string $channel) : array
    {
        $matchedCallback = false;
        $arguments = [];

        foreach ($this->actionsMap[BaseAction::TYPE_SUBSCRIBE] as $key => $callback) {
            $regexp = str_replace('/', '\/', $key);
            $regexp = '/'.preg_replace('/:[^\\\\\/]+/m', '([^\/]+)', $regexp).'/m';
            preg_match_all($regexp, $channel, $matches, PREG_SET_ORDER, 0);

            if ($matches) {
                $arguments = $matches[0];
                array_shift($arguments);
                $matchedCallback = $callback;
                break;
            }
        }

        return [$matchedCallback, $arguments];
    }

    /**
     * @param string|\Closure $callback
     * @param mixed ...$params
     * @return mixed
     * @throws \ReflectionException
     */
    protected function callAction($callback, ...$params)
    {
        if (!is_string($callback)) {
            return call_user_func_array($callback, $params);
        }

        [$class, $method] = explode('@', $callback);

        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Invalid action callback class $class");
        }

        if (!method_exists($class, $method)) {
            throw new \InvalidArgumentException("Invalid action callback method $class::$method");
        }

        return call_user_func_array([new $class, $method], $params);
    }

    protected function createProcessableActionForCallback($callback, array $command) : ProcessableAction
    {
        $method = is_callable($callback)
            ? new \ReflectionFunction($callback)
            : new \ReflectionMethod(str_replace('@', '::', $callback));

        $actionClassName = ProcessableAction::class;

        $firstCallbackParameter = $method->getParameters()[0] ?? null;
        if ($firstCallbackParameter && $firstCallbackParameter->getType()) {
            $actionClassName = $firstCallbackParameter->getType();
        }

        return call_user_func("$actionClassName::createFromCommand", $command);
    }
}
