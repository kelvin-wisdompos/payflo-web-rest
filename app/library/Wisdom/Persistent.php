<?php

namespace Wisdom;

class Persistent
{
    protected $config;
    protected $cache;
    protected $key;

    public function __construct(\Phalcon\Config $Config, \Phalcon\Cache\Backend\File $Cache)
    {
        $this->config = $Config;
        $this->cache  = $Cache;
        $this->key    = $Config->cache->datakey;
    }

    protected function getKey($name)
    {
        return $this->key . md5($name);
    }

    public function getData($name)
    {
        $data = $this->cache->get($this->getKey($name));
        return unserialize($data);
    }

    public function setData($name, $data)
    {
        $this->cache->save($this->getKey($name), serialize($data));
    }
}
