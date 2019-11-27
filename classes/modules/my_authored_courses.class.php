<?php

namespace local_my\module;

use context_course;

require_once($CFG->dirroot.'/local/my/classes/modules/my_courses.class.php');

class my_authored_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'authored_courses';
        $this->modulename = get_string('myauthoringcourses', 'local_my');

        $this->options['withteachersignals'] = true;
        $this->options['noprogress'] = true;
    }

    public function get_courses() {
        global $USER, $DB, $CFG;

        $this->courses = enrol_get_my_courses('id, shortname, fullname');

        foreach (array_keys($this->courses) as $cid) {
            $context = context_course::instance($cid);
            if (!has_capability('local/my:isauthor', $context, $USER->id, false)) {
                // Exclude courses where i'm NOT student.
                self::add_debuginfo("Course Exclude (course $cid not student in)\n", $cid);
                unset($this->courses[$cid]);
            } else {
                self::add_debuginfo("Course Add (course $cid as enrolled in)\n", $cid);
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