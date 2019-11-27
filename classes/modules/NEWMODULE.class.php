<?php

namespace local_my\module;

class NEWMODULE_module extends module {

    public function __construct() {
        $this->area = 'NEWMODULE';
        $this->modulename = get_string('newmodule', 'local_my');
    }

    public function render($required = '') {
        /* implement rendering her */
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}