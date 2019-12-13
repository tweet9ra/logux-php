<?php

require_once "vendor/autoload.php";

$input = file_get_contents('php://input');
$input = '{
  "version": 2,
  "password": "secret",
  "commands": [
    [
      "action",
      { "type": "logux/subscribe", "channel": "chat/123/456" },
      { "id": "1560954012858 38:Y7bysd:O0ETfc 0", "time": 1560954012858 }
    ]
  ]
}';
$inputDecoded = json_decode($input, true);

// Creating app
$app = \tweet9ra\Logux\App::getInstance()
    ->loadConfig('secret', 'http://localhost:31338');

class Foo {
    /**
     * Processing subscribe action
     * @param \tweet9ra\Logux\ProcessableAction $action
     * @return void Return result of actions callback is not used, you must affect Action Object
     */
    public function subscribe (\tweet9ra\Logux\ProcessableAction $action, string $chatId, int $channelId)
    {
        $action->approved()
            ->processed();
    }

    /**
     * Check user credentials
     * @param string $userId
     * @param string $token
     * @param string $authId Authentication command ID
     * @return bool
     */
    public function auth(string $userId, string $token, string $authId) : bool
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