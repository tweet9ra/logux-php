<?php


namespace Tests\Unit;


use Tests\TestCase;

class AuthCommandTest extends TestCase
{
    protected $successfulAuthCommand = ["auth", "38", "good-token", "gf4Ygi6grYZYDH5Z2BsoR"];
    protected $noTokenProvidedExceptionMessage = 'Expected exception error message when token has bad format';

    /**
     * @dataProvider dataProvider
     * @param array $authCommand
     * @param array $expectedResponse
     */
    public function testProcessCorrectAuthCommand(array $authCommand, array $expectedResponse)
    {
        $this->app->setActionsMap([
            'auth' => function (string $authId, string $userId = null, string $token = null) {
                // Allowing access to all guests
                if (!$userId) {
                    return true;
                }

                // Throwing error
                if (!$token) {
                    throw new \Exception($this->noTokenProvidedExceptionMessage);
                }

                $successfulAuthCommand = $this->successfulAuthCommand;
                return $authId == $successfulAuthCommand[3]
                    && $userId == $successfulAuthCommand[1]
                    && $token == $successfulAuthCommand[2];
            }
        ]);

        $successResponse = $this->app->processCommands([$authCommand]);
        $this->assertSame([$expectedResponse], $successResponse);
    }

    public function dataProvider()
    {
        return [
            // Successful user authentication
            [
                $this->successfulAuthCommand,
                ['authenticated', 'gf4Ygi6grYZYDH5Z2BsoR']
            ],
            // Successful guest authentication
            [
                ["auth", false, null, "gf4Ygi6grYZYDH5Z2BsoR"],
                ['authenticated', 'gf4Ygi6grYZYDH5Z2BsoR']
            ],
            // Failed atuhentication caused by bad token
            [
                ["auth", "38", "bad-token", "gf4Ygi6grYZYDH5Z2BsoR"],
                ['denied', 'gf4Ygi6grYZYDH5Z2BsoR']
            ],
            // Authentication failed with error message
            [
                ["auth", "38", false, "gf4Ygi6grYZYDH5Z2BsoR"],
                ['error', $this->noTokenProvidedExceptionMessage]
            ],
        ];
    }
}