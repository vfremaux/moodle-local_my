<?php

namespace local_my\module;

use \StdClass;
use \moodle_url;

class course_search_module extends module {

    public function __construct() {
        $this->area = 'course_search';
        $this->modulename = get_string('mycalendar', 'local_my');
    }

    public function render($required = '') {
        global $PAGE, $OUTPUT;

        $renderer = $PAGE->get_renderer('course');

        $search = optional_param('search', '', PARAM_TEXT);

        $template = new StdClass;
        $template->area = $this->area;
        $template->modulename = $this->modulename;
        $template->coursesearchform = $renderer->course_search_form($search, 'plain');

        return $OUTPUT->render_from_template('local_my/course_search_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}