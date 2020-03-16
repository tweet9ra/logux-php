<?php

namespace Tests;

use tweet9ra\Logux\App;
use tweet9ra\Logux\CommandsProcessor;
use tweet9ra\Logux\EventsHandler;
use tweet9ra\Logux\StackActionsDispatcher;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var App $app Clear app instance for each test
     */
    protected $app;

    /**
     * @var StackActionsDispatcher $actionsDispatcher
     */
    protected $actionsDispatcher;

    protected function setUp(): void
    {
        $this->buildTestApp();
        parent::setUp();
    }

    protected function buildTestApp()
    {
        $eventsHandler = new EventsHandler();
        $actionsDispatcher = new StackActionsDispatcher();

        $this->app = new App(new CommandsProcessor($eventsHandler), $actionsDispatcher, $eventsHandler, '0000');
        $this->actionsDispatcher = $actionsDispatcher;
    }
}