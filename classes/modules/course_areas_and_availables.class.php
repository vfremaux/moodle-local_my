<?php

namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/course_areas.class.php');
require_once($CFG->dirroot.'/local/my/classes/modules/available_courses.class.php');

class course_areas_and_availables_module extends course_areas_module {

    public static $areakey = 'courseareas';
    public static $areaconfigkey = 'coursearea';

    public function __construct() {
        parent::__construct();
        $this->area = 'course_areas_and_availables';
        $this->modulename = get_string('courseareasandavailables', 'local_my');

        $this->options = array();
        $this->options['withcats'] = self::$config->printcategories;
        $this->options['gaugewidth'] = 60;
        $this->options['gaugeheight'] = 15;
    }

    public function get_courses() {
        global $USER, $DB;

        parent::get_courses();

        // Get all available courses.
        foreach ($this->retainedcategories as $area => $areacats) {
            $module = new \local_my\module\available_courses_module();
            $module->set_option('incategories', $areacats);
            $module->set_option('withanonymous', true);
            $module->get_courses();
            $availables = $module->export_courses();
            foreach ($availables as $id => &$c) {
                $c->lastaccess = 0;
            }
            $this->courses = array_merge($this->courses, $availables);
        }
    }
}