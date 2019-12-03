<?php

namespace local_my\module;

use \StdClass;
use \moodle_url;

class my_caption_module extends module {

    public function __construct() {
        $this->area = 'my_caption';
        $this->modulename = '';
    }

    public function render($required = '') {
        global $CFG, $OUTPUT;

        $template = new StdClass;
        $template->area = $this->area;
        if (file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
            include_once($CFG->dirroot.'/local/staticguitexts/lib.php');
            $template->caption = local_print_static_text('my_caption_static_text', new moodle_url('/my/index.php'), false, true);
        } else {
            $template->caption = $OUTPUT->notification(get_string('nostaticguitexts', 'local_my', 'my_caption'));
        }

        return $OUTPUT->render_from_template('local_my/my_caption_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}