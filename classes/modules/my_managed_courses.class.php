<?php

namespace local_my\module;

class my_managed_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'managed_courses';
        $this->modulename = get_string('mymanagedcourses', 'local_my');

        $this->options['withteachersignals'] = true;
        $this->options['noprogress'] = true;
    }

    public function get_courses() {
        global $USER, $DB;

        $capability = 'local/my:ismanager';
        $fields = 'id,shortname,fullname,visible';
        if ($this->courses = local_get_user_capability_course($capability, $USER->id, false, '', 'cc.sortorder, c.sortorder')) {
            foreach ($this->courses as $m) {
                $this->courses[$m->id] = $DB->get_record('course', ['id' => $m->id], $fields);
                self::add_debuginfo("Accept {$m->id} as managed", $m->id);
            }

            $this->process_excluded();
            $this->process_metas();
            $this->process_courseareas();
        }
    }

    protected function has_content($template) {
        return !empty($this->courses) || !empty($template->buttons);
    }

    protected function get_buttons() {
        $mycatlist = local_my_get_catlist('moodle/course:create');
        return self::$renderer->course_creator_buttons($mycatlist);
    }
}