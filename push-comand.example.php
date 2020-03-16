<?php

use tweet9ra\Logux\App;
use tweet9ra\Logux\CommandsProcessor;
use tweet9ra\Logux\CurlActionsDispatcher;
use tweet9ra\Logux\DispatchableAction;
use tweet9ra\Logux\EventsHandler;

require_once "vendor/autoload.php";

// Creating app
$eventsHandler = new EventsHandler();
$app = new App(
    new CommandsProcessor($eventsHandler),
    new CurlActionsDispatcher('http://localhost:31338'),
    $eventsHandler,
    'secret'
);

// Dispatch actions
$app->dispatchActions([
    [
        'NEW_CHAT_MESSAGE',
        ['channels' => ['chats/1337']],
        ['message' => 'hello word']
    ]
]);

$action = (new DispatchableAction)
    ->setType('NEW_CHAT_MESSAGE')
    ->sendTo('channels', ['chats/1337'])
    ->setArguments(['message' => 'hello world'])
    ->setArgument('textColor', 'red');

$app->dispatchAction($action);