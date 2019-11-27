<?php


namespace local_my\module;


class my_courses_grid_module extends my_courses_module {

    public function render($required = 'aslist') {
        return parent::render('asgrid');
    }
}