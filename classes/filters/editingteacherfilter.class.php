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
require_once($CFG->dirroot.'/local/my/classes/filters/filter.class.php');

use \local_my\module\module;
use \context_course;

class coursefilter_editingteacher extends coursefilter {

    /**
     * Apply filter for current $USER (implicit)
     * @param object $module the widget module.
     */
    function apply(module $module) {
        global $USER;

        if ($this->currentvalue == '*') {
            // Quick perf trap. No filtering.
            return;
        }

        $courseids = array_keys($module->get_courses_internal());
        foreach ($courseids as $cid) {
            $context = context_course::instance($cid);
            $canedit = has_capability('moodle/course:manageactivities', $context, $USER->id, false);
            switch ($this->currentvalue) {
                case 'canedit': {
                    if (!$canedit) {
                        $module->remove_course($cid);
                    }
                    break;
                }
                case 'cannotedit': {
                    if ($canedit) {
                        $module->remove_course($cid);
                    }
                    break;
                }
            }
        }
    }
}