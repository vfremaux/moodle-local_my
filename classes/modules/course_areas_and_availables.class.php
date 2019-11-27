<?php

namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/course_areas.class.php');

class course_areas_and_availables_module extends course_areas_module {

    public function __construct() {
        parent::__construct();
        $this->areakey = 'courseareas';
        $this->areaconfigkey = 'courseareas';
        $this->area = 'course_areas_and_availables';
        $this->modulename = get_string('courseareasandavailables', 'local_my');

        $this->options = array();
        $this->options['withcats'] = self::$config->printcategories;
        $this->options['gaugewidth'] = 60;
        $this->options['gaugeheight'] = 15;
    }
}