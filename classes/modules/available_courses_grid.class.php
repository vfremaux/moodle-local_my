<?php


namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/available_courses.class.php');

class available_courses_grid_module extends available_courses_module {

    public function render($required = 'aslist') {
        return parent::render('asgrid');
    }
}