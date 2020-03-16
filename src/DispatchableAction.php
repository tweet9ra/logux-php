<?php


namespace tweet9ra\Logux;


class DispatchableAction extends BaseAction
{
    /**
     * Get action representation that can be sent to logux as command
     * @return array
     */
    public function toCommand() : array
    {
        return [
            'action',
            array_merge(['type' => $this->_type], $this->_arguments),
            array_merge(['reasons' => $this->_reasons], $this->_recepients)
        ];
    }

    public function setArguments(array $arguments) : self
    {
        $this->_arguments = $arguments;
        return $this;
    }

    public function setArgument($key, $value) : self
    {
        $this->_arguments[$key] = $value;
        return $this;
    }
}