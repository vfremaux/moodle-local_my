<?php

namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/course_areas.class.php');

use \StdClass;
use \moodle_url;

class course_areas2_module extends course_areas_module {

    public function __construct() {
        $this->areakey = 'courseareas2';
        $this->areaconfigkey = 'coursearea2_';
        $this->area = 'course_areas2';
        $this->modulename = get_string('courseareas', 'local_my');
    }

}