<?php

namespace local_my\module;

use \StdClass;
use \context_system;

class left_edition_column_module extends module {

    public function __construct() {
        $this->area = 'left_edition_column';
        $this->modulename = get_string('lefteditioncolumn', 'local_my');
    }

    public function render($required = '') {
        global $OUTPUT, $CFG;

        $template = new StdClass;

        if (is_dir($CFG->dirroot.'/local/statiguitexts')) {
            // In case the local_staticguitexts is coming with.
            include_once($CFG->dirroot.'/local/statiguitexts/lib.php');
            $template->content = local_print_static_text('my_caption_left_column_static_text', $CFG->wwwroot.'/my/index.php', false, true);
        } else {
            // Fallback on admin editable in language package.
            $template->content = get_string('defaultleftcolumntext', 'local_my');
        }

        return $OUTPUT->render_from_template('local_my/left_edition_column_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}