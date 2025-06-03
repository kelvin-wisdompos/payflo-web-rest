<?php

namespace Wisdom;

class Firewall
{
    protected $config;
    protected $micro;
    protected $cache;
    protected $key;

    public function __construct(\Phalcon\Config $Config, \Phalcon\Mvc\Micro $Micro, \Phalcon\Cache\Backend\File $Cache)
    {
        $this->config = $Config;
        $this->micro  = $Micro;
        $this->cache  = $Cache;
        $this->key    = $Config->cache->firewallkey .
            md5($Micro->request->getClientAddress());
    }

    public function getHeat()
    {
        $heat = $this->cache->get($this->key, $this->config->firewall->lifetime);
        return intval($heat);
    }

    public function setHeat($heat)
    {
        $this->cache->save($this->key, intval($heat), $this->config->firewall->lifetime);
    }

    public function increaseHeat()
    {
        $this->setHeat($this->getHeat() + $this->config->firewall->increment);
    }

    public function isBlock()
    {
        if ($this->config->status === 'development') {
            return false;
        }
        return $this->getHeat() >= $this->config->firewall->cap;
    }
}
