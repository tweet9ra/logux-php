<?php


namespace tweet9ra\Logux;

class App
{
    protected $controlPassword;
    protected $controlUrl;
    protected $version;

    /** @var CommandsProcessor $commandsProcessor */
    protected $commandsProcessor;

    /** @var ActionsDispatcherInterface $actionsDispatcher */
    protected $actionsDispatcher;

    /** @var EventsHandler $eventsHandler */
    private $eventsHandler;

    /** @var self $instance */
    private static $instance;

    private function __construct() {
        $this->commandsProcessor = new CommandsProcessor();
        $this->eventsHandler = new EventsHandler();
        $this->actionsDispatcher = new CurlActionsDispatcher();
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

    public function getControlUrl()
    {
        return $this->controlUrl;
    }

    public function getControlPassword()
    {
        return $this->controlPassword;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public static function getEventsHandler()
    {
        return self::$instance->eventsHandler;
    }

    public function setActionsDispatcher(ActionsDispatcherInterface $dispatcher)
    {
        $this->actionsDispatcher = $dispatcher;
        return $this;
    }

    public function getActionsDispatcher() : ActionsDispatcherInterface
    {
        return $this->actionsDispatcher;
    }

    /**
     * Handle request from Logux server
     * @param array $loguxRequest
     * @return array
     */
    public function processRequest(array $loguxRequest) : array
    {
        if (!$this->checkControlPassword($loguxRequest['password'])) {
            throw new \InvalidArgumentException('Invalid logux control password');
        }

        return $this->processCommands($loguxRequest['commands']);
    }

    /**
     * Process commands
     * @param array $commands
     * @return array
     */
    public function processCommands(array $commands) : array
    {
        $processedCommands = [];

        foreach ($commands as $commandData) {
            $processedCommands = array_merge(
                $processedCommands,
                $this->commandsProcessor->processCommand($commandData)
            );
        }

        return $processedCommands;
    }

    public function setActionsMap(array $actionsMap) : self
    {
        $this->commandsProcessor->setActionsMap($actionsMap);
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

        $this->getActionsDispatcher()->dispatch([
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

    public function processAuth(string $authId, $userId, $token) : bool
    {
        if (!isset($this->actionsMap['auth'])) {
            throw new \LogicException('Auth handler is not specified');
        }

        return $this->callFunction($this->actionsMap['auth'], $authId, $userId, $token);
    }
}