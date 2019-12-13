<?php


namespace tweet9ra\Logux;

class ProcessableAction extends BaseAction
{
    /** @var array $log */
    protected $log = [];

    public function approved() : self
    {
        $this->log[] = 'approved';
        return $this;
    }

    public function processed() : self
    {
        $this->log[] = 'processed';
        return $this;
    }

    public function unknownChannel() : self
    {
        if ($this->type !== BaseAction::TYPE_SUBSCRIBE) {
            throw new \BadMethodCallException(
                'Only action with type'.BaseAction::TYPE_SUBSCRIBE
                .' can return error "unknownChannel"'
            );
        }

        $this->log[] = 'unknownChannel';
        return $this;
    }

    /**
     * App cant handle action of this type
     * @return $this
     */
    public function unknownAction() : self
    {
        if ($this->type === BaseAction::TYPE_SUBSCRIBE) {
            throw new \BadMethodCallException(
                'You must specify '.BaseAction::TYPE_SUBSCRIBE
                .'. So it cannot return error "unknownAction"'
            );
        }

        $this->log[] = 'unknownAction';
        return $this;
    }

    /**
     * A server error occurred while processing the Action
     * @param string $error
     * @return $this
     */
    public function error(string $error) : self
    {
        $this->log['error'] = $error;
        return $this;
    }

    public function getLog()
    {
        return $this->log;
    }

    /**
     * Get action response representation on Logux execute command request
     * @return array
     */
    public function toLoguxResponse() : array
    {
        // Handle internal server error
        if (isset($this->log['error'])) {
            return ['error', $this->log['error']];
        }

        $response = [];
        if ($this->recepients) {
            $response['resend'] = $this->recepients;
        }

        $response = array_merge($response, array_map(function ($logType) {
            return [$logType, $this->getId()];
        }, $this->log));

        return $response;
    }

    public static function createFromCommand(array $command) : self
    {
        $action = $command[1];
        $meta = $command[2];

        $type = $action['type'];
        unset($action['type']);
        $arguments = $action;

        $instance = new self;

        $instance->type = $type;
        $instance->arguments = $arguments;

        $instance->setId($meta['id']);
        $instance->time = $meta['time'];

        return $instance;
    }
}