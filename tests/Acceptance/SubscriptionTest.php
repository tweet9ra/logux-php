<?php


namespace Tests\Acceptance;


use Tests\Classes\LoguxController;
use Tests\TestCase;
use tweet9ra\Logux\DispatchableAction;
use tweet9ra\Logux\ProcessableAction;

class SubscriptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setActionsMap([
            'logux/subscribe' => [
                'test/:arg1/:arg2' => LoguxController::class . '@subscribeToChannel',
                'test/:arg1' => function (ProcessableAction $action, $arg1) {
                    $this->assertSame('arg1value', $arg1);

                    $this->app->dispatchAction(
                        (new DispatchableAction())
                            ->setType('DISPATCHED_ACTION_TYPE')
                            ->setArgument('arg1', 'arg1value')
                            ->sendTo('users', '1')
                    );
                },
                'test' => function (ProcessableAction $action) {
                    $action->error('Error message');
                },
                'test2' => function (ProcessableAction $action) {
                    throw new \Exception('Exception error message');
                }
            ]
        ]);
    }

    /**
     * @dataProvider dataProvider
     * @param array $request
     * @param array $expectedResponse
     */
    public function testSubcription(array $request, array $expectedResponse)
    {
        $successResponse = $this->app->processCommands([$request]);

        $this->assertSame($expectedResponse, $successResponse);
    }

    public function dataProvider()
    {
        return [
            [
                [
                    "action",
                    ['type' => 'logux/subscribe', 'channel'=> 'test/arg1value/arg2value'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["approved", "1560954012858 1:Y7bysd:O0ETfc 0"],
                    ["processed", "1560954012858 1:Y7bysd:O0ETfc 0"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'logux/subscribe', 'channel'=> 'test/arg1value'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["approved", "1560954012858 1:Y7bysd:O0ETfc 0"],
                    ["processed", "1560954012858 1:Y7bysd:O0ETfc 0"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'logux/subscribe', 'channel'=> 'channel/that/not/exists'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["unknownChannel", "1560954012858 1:Y7bysd:O0ETfc 0"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'logux/subscribe', 'channel'=> 'test'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["error", "Error message"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'logux/subscribe', 'channel'=> 'test2'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["error", "Exception error message"]
                ]
            ]
        ];
    }

    public function testSubcriptionAndCheckDispatchedAction()
    {
        $successResponse = $this->app->processCommands([[
            "action",
            ['type' => 'logux/subscribe', 'channel'=> 'test/arg1value'],
            ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
        ]]);

        $this->assertSame([
            ["approved", "1560954012858 1:Y7bysd:O0ETfc 0"],
            ["processed", "1560954012858 1:Y7bysd:O0ETfc 0"]
        ], $successResponse);

        $this->assertTrue((bool)$this->actionsDispatcher->search('DISPATCHED_ACTION_TYPE'), 'Action was not dispatched');
    }
}