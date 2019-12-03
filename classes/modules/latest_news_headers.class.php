<?php

namespace local_my\module;

class latest_news_headers_module extends latest_news_module {

    public function __construct() {
        $this->area = 'latest_news';
        $this->modulename = get_string('latestnews', 'local_my');
    }

    public function render($required = 'plain') {
        return parent::render('header');
    }

    public function get_courses() {
        // no course related.
        assert(1);
    }
}