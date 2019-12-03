<?php


namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/my_authored_courses.class.php');

class my_authored_courses_grid_module extends my_authored_courses_module {

    public function render($required = 'aslist') {
        return parent::render('asgrid');
    }
}