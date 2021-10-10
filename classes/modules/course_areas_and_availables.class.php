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

require_once($CFG->dirroot.'/local/my/classes/modules/course_areas.class.php');
require_once($CFG->dirroot.'/local/my/classes/modules/available_courses.class.php');

class course_areas_and_availables_module extends course_areas_module {

    public static $areakey = 'courseareas';
    public static $areaconfigkey = 'coursearea';

    public function __construct() {
        parent::__construct();
        $this->area = 'course_areas_and_availables';
        $this->modulename = get_string('courseareasandavailables', 'local_my');

        $this->options = array();
        $this->options['withcats'] = self::$config->printcategories;
        $this->options['gaugewidth'] = 60;
        $this->options['gaugeheight'] = 15;
    }

    public function get_courses() {
        global $USER, $DB;

        parent::get_courses();

        // Get all available courses.
        foreach ($this->retainedcategories as $area => $areacats) {
            $module = new \local_my\module\available_courses_module();
            $module->set_option('incategories', $areacats);
            $module->set_option('withanonymous', true);
            $module->set_option('noexcludefromstream', true);
            $module->get_courses();
            $availables = $module->export_courses();
            foreach ($availables as $id => &$c) {
                $c->lastaccess = 0;
                $this->courses[$id] = $c;
            }
        }

        $this->process_metas();
        $this->process_excluded();
    }
}