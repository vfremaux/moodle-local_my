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

class my_managed_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'my_managed_courses';
        $this->modulename = get_string('mymanagedcourses', 'local_my');

        $this->options['withteachersignals'] = true;
        $this->options['noprogress'] = true;
    }

    public function get_courses() {
        global $USER, $DB;

        $capability = 'local/my:iscoursemanager';
        $fields = 'id,shortname,fullname,visible,category';
        if ($courses = get_user_capability_course($capability, $USER->id, false, '')) {
//        if ($this->courses = local_get_user_capability_course($capability, $USER->id, false, '', 'cc.sortorder, c.sortorder')) {
            foreach ($courses as $m) {
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