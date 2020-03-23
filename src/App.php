<?php


namespace tweet9ra\Logux;

class App
{
    /**
     * @var string $controlPassword
     */
    protected $controlPassword;

    /**
     * @var int $protocolVersion
     */
    protected $protocolVersion;

    /** @var CommandsProcessor $commandsProcessor */
    protected $commandsProcessor;

    /** @var ActionsDispatcherBase $actionsDispatcher */
    protected $actionsDispatcher;

    /** @var EventsHandler $eventsHandler */
    private $eventsHandler;

    public function __construct(
        CommandsProcessorBase $commandsProcessor,
        ActionsDispatcherBase $actionsDispatcher,
        EventsHandlerInterface $eventsHandler,
        string $controlPassword,
        int $protocolVersion = 2
    ) {
        $this->commandsProcessor = $commandsProcessor;
        $this->actionsDispatcher = $actionsDispatcher;
        $this->eventsHandler = $eventsHandler;
        $this->controlPassword = $controlPassword;
        $this->protocolVersion = $protocolVersion;
    }

    public function getEventsHandler(): EventsHandlerInterface
    {
        return $this->eventsHandler;
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

        $this->actionsDispatcher->dispatch([
            'password' => $this->controlPassword,
            'version' => $this->protocolVersion,
            'commands' => $commands
        ]);

        return $this;
    }

    /**
     * @param DispatchableAction|array $action
     * @return App
     */
    public function dispatchAction($action)
    {
        return $this->dispatchActions([$action]);
    }

    protected function checkControlPassword(string $controlPassword) : bool
    {
        return $this->controlPassword === $controlPassword;
    }
}