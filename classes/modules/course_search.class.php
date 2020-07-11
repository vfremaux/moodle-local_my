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

class course_search_module extends module {

    public function __construct() {
        $this->area = 'course_search';
        $this->modulename = get_string('mycalendar', 'local_my');
    }

    public function render($required = '') {
        global $PAGE, $OUTPUT;

        $renderer = $PAGE->get_renderer('course');

        $search = optional_param('search', '', PARAM_TEXT);

        $template = new StdClass;
        $template->area = $this->area;
        $template->modulename = $this->modulename;
        $template->coursesearchform = $renderer->course_search_form($search, 'plain');

        return $OUTPUT->render_from_template('local_my/course_search_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}