<?php


namespace local_my\module;


class main_content_anchor extends module {

    public function render() {
        return '<a name="localmymaincontent"></a>';
    }

    public function get_courses() {
        // no courses
        assert(1);
    }
}