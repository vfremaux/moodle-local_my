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
defined('MOODLE_INTERNAL') || die();

function xmldb_local_my_upgrade($oldversion = 0) {

    $result = true;

    if ($oldversion < 2016010801) {
        local_my_move_settings();
        upgrade_plugin_savepoint(true, 2016010801, 'local', 'my');
    }

    // Moodle 2.0 break line.

    return $result;
}

function local_my_move_settings() {
    global $DB;

    $pattern = 'localmy';

    if ($configs = $DB->get_records_select('config', " name LIKE ? ", array($pattern.'%'))) {
        foreach ($configs as $cfg) {
            $key = str_replace($pattern, '', $cfg->name);
            set_config($key, $cfg->value, 'local_my');
            $DB->delete_records('config', array('name' => $cfg->name));
        }
    }
}