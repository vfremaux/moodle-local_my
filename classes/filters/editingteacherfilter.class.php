<?php

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