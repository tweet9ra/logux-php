<?php


namespace tweet9ra\Logux;

class App
{
    protected $controlPassword;
    protected $controlUrl;
    protected $actionsMap;
    protected $eventsMap;
    protected $version;

    public const BEFORE_PROCESS_ACTION = 0;
    public const ACTION_ERROR = 1;
    public const AUTH_ACTION_ERROR = 2;

    /** @var SubscriptionMapper $subscriptionMapper */
    protected $subscriptionMapper;

    private static $instance;

    private function __construct() {
        $this->subscriptionMapper = new SubscriptionMapper;
    }
    private function __clone() {}
    private function __wakeup() {}

    public static function getInstance() : self
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function loadConfig(string $password, string $controlUrl, $version = 2)
    {
        $this->controlPassword = $password;
        $this->controlUrl = $controlUrl;
        $this->version = $version;

        return $this;
    }

    public function addEvent($eventType, \Closure $callback)
    {
        $this->eventsMap[$eventType][] = $callback;
    }

    protected function fire($eventType, ...$params)
    {
        if (is_array($this->eventsMap[$eventType])) {
            foreach ($this->eventsMap[$eventType] as $callback) {
                call_user_func_array($callback, $params);
            }
        }
    }

    public function processRequest(array $loguxRequest) : array
    {
        if (!$this->checkControlPassword($loguxRequest['password'])) {
            throw new \InvalidArgumentException('Invalid logux control password');
        }

        $processedCommands = [];

        foreach ($loguxRequest['commands'] as $commandData) {
            if ($this->isAuthCommand($commandData)) {
                try {
                    $authResponse = AuthCommand::createFromCommand($commandData)
                        ->toLoguxResponse();
                } catch (\Exception $e) {
                    $this->fire(self::AUTH_ACTION_ERROR, $e);
                    $authResponse = ['error', $e->getMessage()];
                }

                $processedCommands[] = $authResponse;
            } else {
                $action = ProcessableAction::createFromCommand($commandData);

                $this->fire(self::BEFORE_PROCESS_ACTION, $action);

                $processedCommands = array_merge(
                    $processedCommands,
                    $this->processAction($action)->toLoguxResponse()
                );
            }
        }

        return $processedCommands;
    }

    public function setActionsMap(array $actionsMap) : self
    {
        $this->actionsMap = $actionsMap;
        return $this;
    }

    /**
     * @param array[]|DispatchableAction[] $actions
     * @return App
     */
    public function dispatchActions(array $actions)
    {
        $commands = array_map(function ($action) {
            /** @var array|DispatchableAction $action */
            return is_array($action)
                ? $action
                : $action->toCommand();
        }, $actions);

        (new CurlClient)->request($this->controlUrl, [
            'password' => $this->controlPassword,
            'version' => $this->version,
            'commands' => $commands
        ]);

        return $this;
    }

    protected function checkControlPassword(string $controlPassword) : bool
    {
        return $this->controlPassword === $controlPassword;
    }

    protected function isAuthCommand(array $command) : bool
    {
        return $command[0] === 'auth';
    }

    public function processAuth(string $authId, $userId, $token) : bool
    {
        if (!isset($this->actionsMap['auth'])) {
            throw new \LogicException('Auth handler is not specified');
        }

        return $this->callAction($this->actionsMap['auth'], $authId, $userId, $token);
    }

    protected function processAction(ProcessableAction $action) : ProcessableAction
    {
        if (!isset($this->actionsMap[$action->type])) {
            return $action->unknownAction();
        }

        try {
            if ($action->type == BaseAction::TYPE_SUBSCRIBE
                && is_array($this->actionsMap[BaseAction::TYPE_SUBSCRIBE])
            ) {
                [$callback, $params] = $this->subscriptionMapper
                    ->match(
                        $this->actionsMap[BaseAction::TYPE_SUBSCRIBE],
                        $action->arguments['channel']
                    );

                if (!$callback) {
                    $action->unknownChannel();
                    return $action;
                }

                array_unshift($params, $action);

                $this->callAction($callback, ...$params);
            } else {
                $callback = $this->actionsMap[$action->type];
                $this->callAction($callback, $action);
            }


            if (!$action->getLog()) {
                $action->approved()->processed();
            }

            return $action;

        } catch (\Exception $e) {
            // Handle callback internal errors or bad logic
            $this->fire(self::ACTION_ERROR, $e);
            return $action->error($e->getMessage());
        }
    }

    public function callAction($callback, ...$params)
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
}