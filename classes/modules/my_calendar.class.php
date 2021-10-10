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

class my_calendar_module extends module {

    public function __construct() {
        $this->area = 'my_calendar';
        $this->modulename = get_string('mycalendar', 'local_my');
    }

    public function render($required = '') {
        global $PAGE, $OUTPUT;

        $blockinstance = block_instance('calendar_month');
        $blockinstance->page = $PAGE;
        $content = $blockinstance->get_content();

        if (empty($content->text) && empty($content->footer)) {
            return '';
        }

        $template->content = $content->text;
        $template->footer = $content->footer;

        return $OUTPUT->render_from_template('local_my/block_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}