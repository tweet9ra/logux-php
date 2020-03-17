<?php


namespace Tests\Acceptance;


use Tests\Classes\ExtendedProcessableAction;
use Tests\Classes\LoguxController;
use Tests\TestCase;
use tweet9ra\Logux\DispatchableAction;
use tweet9ra\Logux\ProcessableAction;

class ProcessActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setActionsMap([
            'TEST_ACTION_1' => function (ProcessableAction $action) {
                $this->app->dispatchAction(
                    (new DispatchableAction())
                        ->setType('DISPATCHED_ACTION_TYPE')
                        ->setArgument('arg1', 'arg1value')
                        ->sendTo('users', '1')
                );
            },
            'TEST_ACTION_2' => function (ExtendedProcessableAction $action) {
                $this->assertSame('arg1value', $action->arg1);
            },
            'TEST_ACTION_3' => LoguxController::class . '@processActionAndResend',
            'TEST_ACTION_4' => function (ProcessableAction $action) {
                $action->error('Error message');
            },
            'TEST_ACTION_5' => function (ProcessableAction $action) {
                throw new \Exception('Exception error message');
            },
        ]);
    }

    /**
     * @dataProvider dataProvider
     * @param array $request
     * @param array $expectedResponse
     */
    public function testProcessActions(array $request, array $expectedResponse)
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
                    ['type' => 'TEST_ACTION_2', 'arg1' => 'arg1value'],
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
                    ['type' => 'TEST_ACTION_3'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["resend", "1560954012858 1:Y7bysd:O0ETfc 0", ['channels' => ['channel/1337']]],
                    ["approved", "1560954012858 1:Y7bysd:O0ETfc 0"],
                    ["processed", "1560954012858 1:Y7bysd:O0ETfc 0"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'TEST_ACTION_4'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["error", "Error message"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'TEST_ACTION_5'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["error", "Exception error message"]
                ]
            ],
            [
                [
                    "action",
                    ['type' => 'TEST_ACTION_UNDEFINED'],
                    ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
                ],
                [
                    ["unknownAction", "1560954012858 1:Y7bysd:O0ETfc 0"]
                ]
            ]
        ];
    }

    public function testProcessActionAndCheckDispatchable()
    {
        $successResponse = $this->app->processCommands([[
            "action",
            ['type' => 'TEST_ACTION_1'],
            ['id'=> "1560954012858 1:Y7bysd:O0ETfc 0", 'time'=> 1560954012858]
        ]]);

        $this->assertSame([
            ["approved", "1560954012858 1:Y7bysd:O0ETfc 0"],
            ["processed", "1560954012858 1:Y7bysd:O0ETfc 0"]
        ], $successResponse);

        $this->assertTrue((bool)$this->actionsDispatcher->search('DISPATCHED_ACTION_TYPE'), 'Action was not dispatched');
    }
}