<?php

namespace local_my\module;

use \StdClass;

class block_module extends module {

    protected $blockid;

    public function __construct($blockid = 0) {
        $this->blockid = $blockid;
        $this->area = 'block';
        $this->modulename = get_string('block', 'local_my');
    }

    public function render($required = '') {
        global $DB, $OUTPUT;

        $template = new StdClass;
        if (!$blockrec = $DB->get_record('block_instances', array('id' => $blockid, 'parentcontextid' => $contextid))) {
            $template->error = $OUTPUT->notification(get_string('errorbadblock', 'local_my'));
        } else {
            $blockinstance = block_instance($blockrec->blockname, $blockrec);
            $template->title = $blockinstance->get_title();
            $content = $blockinstance->get_content()->text;
            $template->content = $content;
        }
        return $OUTPUT->render_from_template('local_my/block', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}