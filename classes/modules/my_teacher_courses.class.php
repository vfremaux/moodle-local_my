<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_my
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_my\module;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot.'/local/my/classes/modules/my_courses.class.php');
require_once($CFG->dirroot.'/local/my/classes/filters/editingteacherfilter.class.php');

use \context_course;
use \local_my\filter\coursefilter_editingteacher;

class my_teacher_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();

        $this->area = 'my_teacher_courses';
        $this->modulename = get_string('myteachercourses', 'local_my');

        $this->options['withteachersignals'] = true;
        $this->options['noprogress'] = false;

        $this->filters = [];
        $options = [
            '*',
            'cannotedit',
            'canedit'
        ];
        $this->filters[] = new coursefilter_editingteacher('filterisediting', $options);
    }

    public function get_courses() {
        global $USER;

        $this->courses = enrol_get_my_courses('id, shortname, fullname');
        foreach (array_keys($this->courses) as $cid) {
            $context = context_course::instance($cid);
            if (!has_capability('local/my:isteacher', $context, $USER->id, false)) {
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

        debug_trace('Fix sorting');
        $this->fix_courses_attributes_for_sorting();
        debug_trace('sorting');
        $this->sort_courses();

        debug_trace('catching filters');
        $this->catch_filters();
        debug_trace('apply filters');
        $this->apply_filters();
        debug_trace('filters applied');
        debug_trace($this->courses);
    }

    protected function get_buttons() {
        $mycatlist = local_my_get_catlist('moodle/course:create');
        return self::$renderer->course_creator_buttons($mycatlist);
    }
}