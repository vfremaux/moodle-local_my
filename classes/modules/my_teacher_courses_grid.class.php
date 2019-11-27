<?php


namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/my_teacher_courses.class.php');

class my_teacher_courses_grid_module extends my_teacher_courses_module {

    public function render($required = 'aslist') {
        return parent::render('asgrid');
    }
}