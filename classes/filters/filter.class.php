<?php

namespace local_my\filter;

require_once($CFG->dirroot.'/local/my/classes/modules/module.class.php');

use \local_my\module\module;

abstract class coursefilter {

    /**
     * Name of the filter.
     */
    public $name;

    /**
     * options.
     */
    public $options;

    /**
     * current filter value
     */
     public $currentvalue = '*';

    public function __construct($name, $options) {
        $this->name = $name;
        $this->options = $options;
    }

    public function has_input_value() {
        return optional_param($this->name, false, PARAM_TEXT);
    }

    /**
     * The moment where the filter gets its curent value.
     */
    public function catchvalue() {
        $this->currentvalue = optional_param($this->name, '*', PARAM_TEXT);
    }

    /**
     * The way the filter filters data.
     */
    abstract function apply(module $module);

}