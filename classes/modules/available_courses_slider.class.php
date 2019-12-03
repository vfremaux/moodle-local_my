<?php


namespace local_my\module;

require_once($CFG->dirroot.'/local/my/classes/modules/available_courses.class.php');

class available_courses_slider_module extends available_courses_module {

    public function __construct() {
        global $PAGE;

        parent::__construct();
        $PAGE->requires->js_call_amd('local_my/slick', 'init');
        $PAGE->requires->js_call_amd('local_my/slickinit', 'init');
        $PAGE->requires->css('/local/my/css/slick.css');

        $this->options['gaugetype'] = 'sektor';
        $this->options['gaugewidth'] = '20';
        $this->options['gaugeheight'] = '20';
    }

    public function render($required = 'asslider') {
        return parent::render('asslider');
    }
}