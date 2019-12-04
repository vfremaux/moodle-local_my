<?php

namespace local_my\module;

use context_course;

class my_teacher_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'teacher_courses';
        $this->modulename = get_string('myteachercourses', 'local_my');

        $this->options['withteachersignals'] = true;
        $this->options['noprogress'] = false;
    }

    public function get_courses() {
        global $USER;

        $this->courses = enrol_get_my_courses('id, shortname, fullname');
        foreach (array_keys($this->courses) as $cid) {
            $context = context_course::instance($cid);
            if (!has_capability('local/my:isteacher', $context, $USER->id, false) &&
                !has_capability('local/my:isauthor', $context, $USER->id, false)) {
                // Exclude courses where i'm NOT student.
                self::add_debuginfo("Course Exclude (course $cid not teacher inside)", $cid);
                unset($this->courses[$cid]);
            } else {
                self::add_debuginfo("Course Add (course $cid as enrolled inside)", $cid);
            }
        }

        $this->process_excluded();
        $this->process_metas();
        $this->process_courseareas();
    }

    protected function get_buttons() {
        $mycatlist = local_my_get_catlist('moodle/course:create');
        return self::$renderer->course_creator_buttons($mycatlist);
    }
}