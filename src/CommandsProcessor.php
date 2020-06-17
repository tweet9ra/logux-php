<?php

namespace tweet9ra\Logux;

use Closure;
use Exception;
use InvalidArgumentException;
use LogicException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

class CommandsProcessor extends CommandsProcessorBase
{
    protected $actionsMap;

    public function setActionsMap(array $actionsMap)
    {
        $this->actionsMap = $actionsMap;
    }

    public function processCommand(array $command) : array
    {
        // Processing auth
        if ($command['command'] === 'auth') {
            try {
                $authResponse = $this->processAuthCommand($command);
            } catch (Exception $e) {
                $this->eventsHandler->fire(EventsHandler::AUTH_ACTION_ERROR, $e, $command);
                $authResponse =  [
                    'answer' => 'denied',
                    'authId' => $command['authId'],
                    'subprotocol' => $command['subprotocol']
                ];
            }

            return [$authResponse];
        }

        try {
            $actionResponse = $this->processActionCommand($command);
        } catch (Exception $e) {
            // Handle callback internal errors or bad logic
            $this->eventsHandler->fire(EventsHandler::ACTION_ERROR, $e, $command);
            $actionResponse = [[
                'answer' => 'error',
                'id' => $command['meta']['id'],
                'details' => $e->getMessage()
            ]];
        }

        return $actionResponse;
    }

    /**
     * @param array $command
     * @return array
     */
    protected function processAuthCommand(array $command)
    {
        if (!isset($this->actionsMap['auth'])) {
            throw new LogicException('Auth handler is not specified');
        }

        $authResult = $this->callAction(
            $this->actionsMap['auth'],
            $command
        );

        return [
            'answer' => $authResult ? 'authenticated' : 'denied',
            'subprotocol' => $command['subprotocol'], // TODO: Implement subprotocol versioning instead of dirty hack,
            'authId' => $command['authId']
        ];
    }

    /**
     * @param array $command
     * @return array|array[]
     * @throws ReflectionException
     */
    protected function processActionCommand(array $command)
    {
        $actionType = $command['action']['type'];
        if (!isset($this->actionsMap[$actionType])) {
            return [[
                'answer' => 'unknownAction',
                'id' => $command['meta']['id']
            ]];
        }

        $params = [];
        if (
            $actionType === BaseAction::TYPE_SUBSCRIBE
            && is_array($this->actionsMap[BaseAction::TYPE_SUBSCRIBE])
        ) {
            // Processing subscription action if subscription map defined as array
            [$callback, $params] = $this->matchSubscriptionChannel($command['action']['channel']);
            if (!$callback) {
                return [['unknownChannel', $command['meta']['id']]];
            }
        } else {
            $callback = $this->actionsMap[$actionType];
        }

        $action = $this->createProcessableActionForCallback($callback, $command);

        $this->eventsHandler->fire(EventsHandler::BEFORE_PROCESS_ACTION, $action);

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
            $regexp = '/^'.preg_replace('/:[^\\\\\/]+/m', '([^\/]+)', $regexp).'$/m';
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
     * @param string|Closure $callback
     * @param mixed ...$params
     * @return mixed
     */
    protected function callAction($callback, ...$params)
    {
        if (!is_string($callback)) {
            return call_user_func_array($callback, $params);
        }

        [$class, $method] = explode('@', $callback);

        if (!class_exists($class)) {
            throw new InvalidArgumentException("Invalid action callback class $class");
        }

        if (!method_exists($class, $method)) {
            throw new InvalidArgumentException("Invalid action callback method $class::$method");
        }

        return call_user_func_array([new $class, $method], $params);
    }

    /**
     * @param $callback
     * @param array $command
     * @return ProcessableAction
     * @throws ReflectionException
     */
    protected function createProcessableActionForCallback($callback, array $command) : ProcessableAction
    {
        $method = is_callable($callback)
            ? new ReflectionFunction($callback)
            : new ReflectionMethod(str_replace('@', '::', $callback));

        $actionClassName = ProcessableAction::class;

        $firstCallbackParameter = $method->getParameters()[0] ?? null;
        if ($firstCallbackParameter && $firstCallbackParameter->getType()) {
            $actionClassName = $firstCallbackParameter->getType();
        }

        return call_user_func("$actionClassName::createFromCommand", $command);
    }
}
