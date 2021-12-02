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

class my_caption_module extends module {

    public function __construct() {
        $this->area = 'my_caption';
        $this->modulename = '';
    }

    public function render($required = '') {
        global $CFG, $OUTPUT;

        $template = new StdClass;
        $template->area = $this->area;
        if (file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
            include_once($CFG->dirroot.'/local/staticguitexts/lib.php');
            $template->caption = local_print_static_text('my_caption_static_text', new moodle_url('/my/index.php'), false, true);
        } else {
            $template->caption = $OUTPUT->notification(get_string('nostaticguitexts', 'local_my', 'my_caption'));
        }

        return $OUTPUT->render_from_template('local_my/my_caption_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}