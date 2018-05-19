<?php

namespace dbcengine;

/**
 * BoundParameter class
 */
class BoundParameter {

    public $name;
    public $value;
    public $type;

    function __construct($name, $value, $type) {
        $this->name  = $name;
        $this->value = $value;
        $this->type  = $type;
    }

}
