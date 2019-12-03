<?php

namespace local_my\module;

use StdClass;

class me_module extends module {

    public function __construct() {
        $this->area = 'me';
        $this->modulename = get_string('me', 'local_my');
    }

    public function render($required = '') {
        global $OUTPUT, $USER;

        $template = new StdClass;
        $template->userpicture = $OUTPUT->user_picture($USER, array('size' => 50));
        $template->username = fullname($USER);

        return $OUTPUT->render_from_template('local_my/me_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}