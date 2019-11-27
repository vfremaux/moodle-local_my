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

$item = required_param('item', PARAM_TEXT); // At start 'authoredcat'.
$catid = optional_param('catid', 0, PARAM_INT);
$hide = optional_param('hide', false, PARAM_BOOL);
$action = optional_param('what', '', PARAM_ALPHA);
$catids = optional_param('catids', '', PARAM_TEXT);

require_login();

if (!empty($action)) {
    $select = ' userid = ? AND name LIKE ? ';
    // Delete all hide keys.
    $DB->delete_records_select('user_preferences', $select, array($USER->id, 'local_my_'.$item.'%'));

    if ($action == 'collapseall') {
        $catidsarr = explode(',', $catids);
        foreach ($catidsarr as $catid) {
            $record = new StdClass;
            $record->userid = $USER->id;
            $record->name = 'local_my_'.$item.'_'.$catid.'_hidden';
            $DB->insert_record('user_preferences', $record);
        }
    }
    die;
}

if (!$coursecat = $DB->get_record('course_categories', array('id' => $catid))) {
    print_error('badcatid');
}

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