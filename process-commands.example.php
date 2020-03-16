<?php

use tweet9ra\Logux\App;
use tweet9ra\Logux\CommandsProcessor;
use tweet9ra\Logux\CurlActionsDispatcher;
use tweet9ra\Logux\EventsHandler;

require_once "vendor/autoload.php";

$input = file_get_contents('php://input');
$inputDecoded = json_decode($input, true);

// Creating app
$eventsHandler = new EventsHandler();
$app = new App(
    new CommandsProcessor($eventsHandler),
    new CurlActionsDispatcher('http://localhost:31338'),
    $eventsHandler,
    'secret'
);

class Foo {
    /**
     * Processing subscribe action
     * @param \tweet9ra\Logux\ProcessableAction $action
     * @return void Return result of actions callback is not used, but you can affect Action Object for response
     */
    public function subscribe (\tweet9ra\Logux\ProcessableAction $action, string $chatId, int $channelId)
    {
        // Not nessessary, this will do automatically if Action was not affected
        $action->approved()
            ->processed();
    }

    /**
     * Check user credentials
     * @param string|null $userId
     * @param string|null $token
     * @param string $authId Authentication command ID
     * @return bool
     */
    public function auth(string $authId, $userId = null, $token = null) : bool
    {
        return true;
    }
}

// You can set callback class method or use closure
$app->setActionsMap([
    'logux/subscribe' => [
        'chat/:chatId/:channelId' => 'Foo@subscribe',
        'chat/:chatId' => function (\tweet9ra\Logux\ProcessableAction $action, string $chatId, int $channelId) {
            $action->approved()
                ->processed();
        }
    ],
    'auth' => 'Foo@auth'
]);

$response = $app->processRequest($inputDecoded);

echo json_encode($response);