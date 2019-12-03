<?php

namespace local_my\module;

use \StdClass;
use \moodle_url;

class admin_stats_module extends module {

    public function __construct() {
        $this->area = 'admin_stats';
        $this->modulename = get_string('sitestats', 'local_my');
    }

    public function render($required = '') {
        global $OUTPUT;

        $template = new StdClass;
        $template->area = $this->area;
        $template->modulename = $this->modulename;
        $template->sitestats = self::$renderer->site_stats();

        return $OUTPUT->render_from_template('local_my/admin_stats_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}