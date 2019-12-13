<?php

require_once "vendor/autoload.php";

// Creating app
$app = \tweet9ra\Logux\App::getInstance()
    ->loadConfig('secret', 'http://localhost:31338');

// Dispatch banch of actions
$app->dispatchActions([
    [
        'NEW_CHAT_MESSAGE',
        ['channels' => ['chats/1337']],
        ['message' => 'hello word']
    ]
]);

// Dispatch single action
(new \tweet9ra\Logux\DispatchableAction)
    ->setType('NEW_CHAT_MESSAGE')
    ->sendTo('channels', ['chats/1337'])
    ->setArguments(['message' => 'hello world'])
    ->setArgument('textColor', 'red')
    ->dispatch();