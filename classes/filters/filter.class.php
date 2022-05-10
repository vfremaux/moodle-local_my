<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_my
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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