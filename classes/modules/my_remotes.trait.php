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

use \context_course;

/**
 * common code to all "remote" wigdets
 */
trait my_remotes {

    public function get_courses() {
        global $USER, $DB, $CFG;

        $myremotes = $DB->get_records('mnetservice_enrol_enrolments', ['userid' => $USER->id]);

        foreach ($myremotes as $rc) {
            $this->courses[$rc->hostid][$rc->remotecourseid] = $DB->get_record('mnetservice_enrol_courses', ['remoteid' => $rc->remotecourseid]);
        }
    }
}