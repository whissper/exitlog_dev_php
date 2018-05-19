<?php

namespace utils;

/**
 * class TemplateProvider
 */
class TemplateProvider {

    private $values = array();

    //constructor
    function __construct() {
        
    }

    /**
     * set text string for keyed parameter in template
     * @param type $key
     * @param type $value
     */
    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    /**
     * load template by its name
     * @param type $name
     * @return type String
     */
    public function loadTemplate($name) {
        $output = file_get_contents('./templates/' . $name . '.tpl');

        foreach ($this->values as $key => $value) {
            $tagToReplace = "[@$key]";
            $output = str_replace($tagToReplace, $value, $output);
        }

        return $output;
    }

}
