<?php


namespace tweet9ra\Logux;


class BaseAction
{
    const TYPE_SUBSCRIBE = 'logux/subscribe';

    public $type;
    public $arguments = [];
    public $reasons = [];

    public $time;

    public $timeId;
    public $userId = false;
    public $tabId;
    public $randomId;
    public $order;

    /** @var array $recepients */
    protected $recepients = [];

    /**
     * @param string $type
     * @param string[]|string $to
     * @return $this
     */
    public function sendTo(string $type, $to) : self
    {
        if (!in_array($type, ['channels', 'users', 'clients', 'nodes'])) {
            throw new \InvalidArgumentException('Invalid action recepient type');
        }

        $this->recepients[$type] = array_merge(
            $this->recepients[$type] ?? [],
            is_array($to) ? $to : [$to]
        );

        return $this;
    }

    public function getId()
    {
        return $this->timeId.' '.$this->getNodeId().' '.$this->order;
    }

    public function getNodeId()
    {
        return "$this->userId:$this->tabId".($this->randomId ? ":$this->randomId" : '');
    }

    public function getClientId()
    {
        return "$this->userId:$this->tabId";
    }

    public function setId(string $id)
    {
        [$this->timeId, $node, $this->order] = explode(' ', $id);
        [$this->userId, $this->tabId, $this->randomId] = explode(':', $node);
    }

    public function setType(string $type) : self
    {
        $this->type = $type;
        return $this;
    }
}