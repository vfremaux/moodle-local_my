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

class my_templates_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'my_templates';
        $this->modulename = get_string('mytemplates', 'local_my');
        $this->options['noprogress'] = true;
    }

    public function get_courses() {
        global $USER, $DB, $CFG;

        // Templating prerequisistes.
        if (!is_dir($CFG->dirroot.'/local/coursetemplates')) {
            return;
        }

        $tconfig = get_config('local_coursetemplates');

        if (!$tconfig->templatecategory) {
            $debuginfo = "Template category is empty or not defined. This has to be checked in configuration.";
            return;
        }

        if (!$DB->record_exists('course_categories', array('id' => $tconfig->templatecategory))) {
            return;
        }

        require_once($CFG->dirroot.'/local/coursetemplates/xlib.php');

        $config = get_config('local_coursetemplates');
        $debug = optional_param('showresolve', false, PARAM_BOOL);

        $templatecatids = local_coursetemplates_get_template_categories();

        $capability = 'local/my:isauthor';
        if ($templates = local_get_user_capability_course($capability, $USER->id, false, '', 'cc.sortorder, c.sortorder')) {
            foreach ($templates as $t) {
                $category = $DB->get_field('course', 'category', ['id' => $t->id]);
                if (in_array($category, $templatecatids)) {
                    $this->courses[$t->id] = $DB->get_record('course', ['id' => $t->id]);
                    self::add_debuginfo("Accept {$t->id} as template", $t->id);
                }
            }
            $this->process_excluded();
            $this->process_metas();
            $this->process_courseareas();
        }
    }

    protected function has_content($template) {
        return !empty($this->courses);
    }
}