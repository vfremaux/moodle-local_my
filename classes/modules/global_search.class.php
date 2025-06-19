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

use \StdClass;
use \moodle_url;

class global_search_module extends module {

    public function __construct() {
        $this->area = 'global_search';
        $this->modulename = get_string('globalsearch', 'local_my');
    }

    public function render($required = '') {
        global $PAGE, $OUTPUT, $CFG;

        $renderer = $PAGE->get_renderer('course');

        $template = new StdClass;

        $search = optional_param('search', '', PARAM_TEXT);
        if (is_dir($CFG->dirroot.'/local/search')) {
            $config = get_config('local_search');
            if (!empty($config->enable)) {
                include($CFG->dirroot.'/local/search/xlib.php');
                $template->coursesearchform = local_search_get_course_search_form($search);
            }
        } else {
            $template->coursesearchform = $OUTPUT->notification('notinstalled', 'local_search');
        }

        $template->area = $this->area;

        return $OUTPUT->render_from_template('local_my/course_search_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}