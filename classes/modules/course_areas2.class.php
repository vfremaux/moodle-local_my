<?php

namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/course_areas.class.php');

use \StdClass;
use \moodle_url;

class course_areas2_module extends course_areas_module {

    public static $areakey = 'courseareas2';
    public static $areaconfigkey = 'coursearea2_';

    public function __construct() {
        parent::__construct();
        $this->area = 'course_areas2';
        $this->modulename = get_string('courseareas', 'local_my');
    }

}