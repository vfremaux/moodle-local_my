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
 * @package     local_my
 * @copyright   2016 onwards Valery Fremaux <http://docs.activeprolearn.com/en>
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

$item = required_param('item', PARAM_ALPHA); // At start 'authoredcat'.
$catid = required_param('catid', PARAM_INT);
$hide = required_param('hide', PARAM_BOOL);

if (!$coursecat = $DB->get_record('course_categories', array('id' => $catid))) {
    print_error('badcatid');
}

require_login();

$hidekey = 'local_my_'.$item.'_'.$catid.'_hidden';
$params = array('userid' => $USER->id, 'name' => $hidekey);
if (!$hide) {
    $DB->delete_records('user_preferences', $params);
} else {
    if ($oldrec = $DB->get_record('user_preferences', $params)) {
        // We should never have as deleting when showing.
        $oldrec->value = 1;
        $DB->update_record('user_preferences', $oldrec);
    } else {
        // Store course id in value to optimise retrieval.
        $newrec = new StdClass;
        $newrec->userid = $USER->id;
        $newrec->name = $hidekey;
        $newrec->value = 1;
        $DB->insert_record('user_preferences', $newrec);
    }
}