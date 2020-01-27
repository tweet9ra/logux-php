<?php


namespace tweet9ra\Logux;


class BaseAction
{
    const TYPE_SUBSCRIBE = 'logux/subscribe';

    public $_type;
    public $_arguments = [];
    public $_reasons = [];

    public $_time;

    public $_timeId;
    public $_userId = false;
    public $_tabId;
    public $_randomId;
    public $_order;

    /** @var array $_recepients */
    protected $_recepients = [];

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

        $this->_recepients[$type] = array_merge(
            $this->_recepients[$type] ?? [],
            is_array($to) ? $to : [$to]
        );

        return $this;
    }

    public function getId()
    {
        return $this->_timeId.' '.$this->getNodeId().' '.$this->_order;
    }

    public function getNodeId()
    {
        return "$this->_userId:$this->_tabId".($this->_randomId ? ":$this->_randomId" : '');
    }

    public function getClientId()
    {
        return "$this->_userId:$this->_tabId";
    }

    public function setId(string $id)
    {
        [$this->_timeId, $node, $this->_order] = explode(' ', $id);
        [$this->_userId, $this->_tabId, $this->_randomId] = explode(':', $node);
    }

    public function setType(string $_type) : self
    {
        $this->_type = $_type;
        return $this;
    }

    public function __set($name, $value)
    {
        $this->_arguments[$name] = $value;
    }

    public function __get($name)
    {
        return $this->_arguments[$name];
    }

    public function userId()
    {
        return $this->_userId;
    }
}