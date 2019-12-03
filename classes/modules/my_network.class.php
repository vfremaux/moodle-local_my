<?php

namespace local_my\module;

use StdClass;

class my_network_module extends module {

    public function __construct() {
        $this->area = 'my_network';
        $this->modulename = get_string('mynetwork', 'local_my');
    }

    public function render($required = '') {
        global $OUTPUT;

        $blockinstance = block_instance('user_mnet_hosts');
        if (empty($blockinstance)) {
            // If user mnet hosts even not installed.
            return '';
        }

        $content = $blockinstance->get_content();
        if (empty($content->items) && empty($content->footer)) {
            return '';
        }

        $template = new StdClass;
        $template->area = $this->area;
        $template->modulename = $this->modulename;

        if (!empty($content->items)) {
            foreach ($content->items as $item) {
                $nodetpl = new StdClass;
                $nodetpl->icon = array_shift($content->icons);
                $nodetpl->item = $item;
                $template->nodes[] = $nodetpl;
            }

        }
        if (!empty($content->footer)) {
            $template->footer = $content->footer;
        }

        return $OUTPUT->render_from_template('local_my/my_network_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}