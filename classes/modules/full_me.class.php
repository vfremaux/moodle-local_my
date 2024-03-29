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
use \context_system;

class full_me_module extends module {

    public function __construct() {
        $this->area = 'full_me';
        $this->modulename = get_string('fullme', 'local_my');
    }

    public function render($required = '') {
        global $OUTPUT, $USER, $CFG;

        $context = context_system::instance();
        $template = new StdClass;

        $identityfields = array_flip(explode(',', $CFG->showuseridentity));

        if (has_capability('moodle/user:viewhiddendetails', $context)) {
            $hiddenfields = array();
        } else {
            $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
        }

        $template->userpicture = $OUTPUT->user_picture($USER, array('size' => 50));
        $template->username = $OUTPUT->heading(fullname($USER));

        $template->inforows = [];
        if (!isset($hiddenfields['firstaccess'])) {
            if ($USER->firstaccess) {
                $datestring = userdate($USER->firstaccess)."&nbsp; (".format_time(time() - $USER->firstaccess).")";
            } else {
                $datestring = get_string("never");
            }
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("firstaccess");
            $inforowtpl->value = $datestring;
            $template->inforows[] = $inforowtpl;
        }
        if (!isset($hiddenfields['lastaccess'])) {
            if ($USER->lastaccess) {
                $datestring = userdate($USER->lastaccess)."&nbsp; (".format_time(time() - $USER->lastaccess).")";
            } else {
                $datestring = get_string("never");
            }
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("lastaccess");
            $inforowtpl->value = $datestring;
            $template->inforows[] = $inforowtpl;
        }

        if (isset($identityfields['institution']) && $USER->institution) {
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("institution");
            $inforowtpl->value = $USER->institution;
            $template->inforows[] = $inforowtpl;
        }

        if (isset($identityfields['department']) && $USER->department) {
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("department");
            $inforowtpl->value = $USER->department;
            $template->inforows[] = $inforowtpl;
        }

        if (isset($identityfields['country']) && !isset($hiddenfields['country']) && $USER->country) {
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("country");
            $inforowtpl->value = get_string($USER->country, 'countries');
            $template->inforows[] = $inforowtpl;
        }

        if (isset($identityfields['city']) && !isset($hiddenfields['city']) && $USER->city) {
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("city");
            $inforowtpl->value = $USER->city;
            $template->inforows[] = $inforowtpl;
        }

        if (isset($identityfields['idnumber']) && $USER->idnumber) {
            $inforowtpl = new StdClass;
            $inforowtpl->label = get_string("idnumber");
            $inforowtpl->value = $USER->idnumber;
            $template->inforows[] = $inforowtpl;
        }

        return $OUTPUT->render_from_template('local_my/full_me_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}