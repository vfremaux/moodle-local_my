<?php


namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/courses.class.php');

class courses_grid_module extends courses_module {

    public function render($required = 'aslist') {
        return parent::render('asgrid');
    }
}