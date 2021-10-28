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

class notavailable_module extends module {

    protected $requiredmodule;

    public function __construct() {
        $this->area = 'notavailable';
        $this->modulename = get_string('notavailable', 'local_my');
    }

    /**
     * Document what module was required before failing.
     */
    public function set_required($requiredmodule) {
        $this->requiredmodule = $requiredmodule;
    }

    public function render($required = '') {
        global $OUTPUT, $USER;

        $template = new StdClass;
        $template->content = $OUTPUT->notification(get_string('notavailablemodule', 'local_my'));

        return $OUTPUT->render_from_template('local_my/notavailable_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}