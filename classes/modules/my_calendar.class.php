<?php

namespace local_my\module;

use \StdClass;
use \moodle_url;

class my_calendar_module extends module {

    public function __construct() {
        $this->area = 'my_calendar';
        $this->modulename = get_string('mycalendar', 'local_my');
    }

    public function render($required = '') {
        global $PAGE;

        $blockinstance = block_instance('calendar_month');
        $blockinstance->page = $PAGE;
        $content = $blockinstance->get_content();

        if (empty($content->text) && empty($content->footer)) {
            return '';
        }

        $template->content = $content->text;
        $template->footer = $content->footer;

        return $OUTPUT->render_from_template('local_my/block_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}