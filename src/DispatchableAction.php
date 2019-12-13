<?php


namespace tweet9ra\Logux;


class DispatchableAction extends BaseAction
{
    public function dispatch()
    {
        return App::getInstance()
            ->dispatchActions([$this]);
    }

    /**
     * Get action representation that can be sent to logux as command
     * @return array
     */
    public function toCommand() : array
    {
        return [
            'action',
            array_merge(['type' => $this->type], $this->arguments),
            array_merge(['reasons' => $this->reasons], $this->recepients)
        ];
    }

    public function setArguments(array $arguments) : self
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function setArgument($key, $value) : self
    {
        $this->arguments[$key] = $value;
        return $this;
    }
}