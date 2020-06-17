<?php


namespace tweet9ra\Logux;

use tweet9ra\Logux\exceptions\BruteForceException;
use tweet9ra\Logux\exceptions\LoguxException;
use tweet9ra\Logux\exceptions\NotSupportedProtocolException;
use tweet9ra\Logux\exceptions\WrongFormatException;
use tweet9ra\Logux\exceptions\WrongSecretException;

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
     * @throws LoguxException
     */
    public function processRequest(array $loguxRequest) : array
    {
        session_id('logux-session');
        session_start();
        $this->checkBruteForce();
        $this->checkFormat($loguxRequest);
        $this->checkControlPassword($loguxRequest['secret']);
        $this->checkProtocolVersion($loguxRequest['version']);

        return $this->processCommands($loguxRequest['commands']);
    }

    /**
     * @param array $loguxRequest
     * @throws WrongFormatException
     */
    public function checkFormat(array $loguxRequest) {
        if (
            !array_key_exists('secret', $loguxRequest)
            || !array_key_exists('version', $loguxRequest)
            || !array_key_exists('commands', $loguxRequest)
        ) {
            throw new WrongFormatException();
        }
    }

    /**
     * @param integer $protocolVersion
     * @throws NotSupportedProtocolException
     */
    public function checkProtocolVersion($protocolVersion)
    {
        if ($this->protocolVersion !== $protocolVersion) {
            throw new NotSupportedProtocolException();
        }
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
            'secret' => $this->controlPassword,
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

    /**
     * @param string $controlPassword
     * @throws WrongSecretException
     */
    protected function checkControlPassword(string $controlPassword)
    {
        if ($this->controlPassword !== $controlPassword) {
            if (!isset($_SESSION['logux_brute_force_attempts'])) {
                $_SESSION['logux_brute_force_attempts'] = 0;
            }
            $_SESSION['logux_brute_force_attempts'] += 1;
            $_SESSION['logux_last_failed_attempt'] = time();

            throw new WrongSecretException();
        }
    }

    /**
     * @throws BruteForceException
     */
    protected function checkBruteForce() {
        if (isset($_SESSION['logux_last_failed_attempt'])) {
            if (time() - $_SESSION['logux_last_failed_attempt'] < 60) {
                if ($_SESSION['logux_brute_force_attempts'] >= 5) {
                    throw new BruteForceException();
                }
            } else {
                unset($_SESSION['logux_last_failed_attempt']);
                unset($_SESSION['logux_brute_force_attempts']);
            }
        }
    }
}