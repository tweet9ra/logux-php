<?php


namespace tweet9ra\Logux;


abstract class CommandsProcessorBase
{
    /**
     * @var EventsHandlerInterface
     */
    protected $eventsHandler;

    public function __construct(EventsHandlerInterface $eventsHandler)
    {
        $this->eventsHandler = $eventsHandler;
    }

    /**
     * Process one command
     * @param array $command
     * @return array [
     *               ["resend", "1560954012838 38:Y7bysd:O0ETfc 0", { "channels": ["users/38"] }],
     *               ["approved", "1560954012838 38:Y7bysd:O0ETfc 0"],
     *               ["processed", "1560954012838 38:Y7bysd:O0ETfc 0"]
     *              ]
     */
    abstract public function processCommand(array $command): array;

    /**
     * Set routes
     * @param array $actionsMap array of routes
     * @return mixed
     */
    abstract public function setActionsMap(array $actionsMap);
}