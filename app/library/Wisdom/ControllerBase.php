<?php

namespace Wisdom;

class ControllerBase extends \Phalcon\Mvc\Controller
{
    protected function getJSONBody()
    {
        $json = json_decode($this->request->getRawBody(), true);
        return is_array($json) ? $json : [];
    }

    public function myClassName()
    {
        return str_replace("Controller", "", get_class($this));
    }
}
