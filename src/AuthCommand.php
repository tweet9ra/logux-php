<?php


namespace tweet9ra\Logux;


class AuthCommand
{
    protected $commandData;

    public function __construct(array $commandData)
    {
        $this->commandData = $commandData;
    }

    public static function createFromCommand(array $commandData) : self
    {
        return new self ($commandData);
    }

    public function toLoguxResponse() : array
    {
        return [
            $this->check() ? 'authenticated' : 'denied',
            $this->commandData[3]
        ];
    }

    public function check() : bool
    {
        return App::getInstance()
            ->processAuth(
                $this->commandData[1],
                $this->commandData[2],
                $this->commandData[3]
            );
    }
}