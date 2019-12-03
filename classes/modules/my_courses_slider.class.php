<?php


namespace local_my\module;


class my_courses_slider_module extends my_courses_module {

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