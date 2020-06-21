<?php


namespace tweet9ra\Logux;

class ProcessableAction extends BaseAction
{
    /** @var array $_log */
    protected $_log = [];

    public function approved() : self
    {
        $this->_log[] = 'approved';
        return $this;
    }

    public function processed() : self
    {
        $this->_log[] = 'processed';
        return $this;
    }

    public function unknownChannel() : self
    {
        if ($this->_type !== BaseAction::TYPE_SUBSCRIBE) {
            throw new \BadMethodCallException(
                'Only action with type'.BaseAction::TYPE_SUBSCRIBE
                .' can return error "unknownChannel"'
            );
        }

        $this->_log[] = 'unknownChannel';
        return $this;
    }

    /**
     * App cant handle action of this type
     * @return $this
     */
    public function unknownAction() : self
    {
        if ($this->_type === BaseAction::TYPE_SUBSCRIBE) {
            throw new \BadMethodCallException(
                'You must specify '.BaseAction::TYPE_SUBSCRIBE
                .'. So it cannot return error "unknownAction"'
            );
        }

        $this->_log[] = 'unknownAction';
        return $this;
    }

    /**
     * A server error occurred while processing the Action
     * @param string $error
     * @return $this
     */
    public function error(string $error) : self
    {
        $this->_log['error'] = $error;
        return $this;
    }

    public function getLog()
    {
        return $this->_log;
    }

    /**
     * Get action response representation on Logux execute command request
     * @return array
     */
    public function toLoguxResponse() : array
    {
        // Handle internal server error
        if (isset($this->_log['error'])) {
            return [[
                'answer' => 'error',
                'details' => $this->_log['error'],
                'id' => $this->getId()
            ]];
        }

        $response = [];
        if ($this->_recepients) {
            $response[] = array_merge([
                'answer' => 'resend',
                'id' => $this->getId()
            ], $this->_recepients);
        }

        $response = array_merge($response, array_map(function ($logType) {
            return [
                'answer' => $logType,
                'id' => $this->getId()
            ];
        }, $this->_log));

        return $response;
    }

    public static function createFromCommand(array $command) : self
    {
        $action = $command['action'];
        $meta = $command['meta'];

        $type = $action['type'];
        unset($action['type']);
        $arguments = $action;

        $instance = new static;

        $instance->_type = $type;
        $instance->_arguments = $arguments;

        $instance->setId($meta['id']);
        $instance->_time = $meta['time'];

        return $instance;
    }
}