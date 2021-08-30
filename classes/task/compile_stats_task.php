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
 * @package local_my
 * @author Valery Fremaux <valery.fremaux@gmail.com>, <valery@edunao.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft Open Technologies, Inc. (http://msopentech.com/)
 */
namespace local_my\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to compile some stats on system.
 */
class compile_stats_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_compile_stats', 'local_my');
    }

    /**
     * Do the job.
     */
    public function execute() {
        global $CFG, $DB;

        $stats = new \StdClass();

        $stats->filesize = get_directory_size($CFG->dataroot);
        $stats->numfiles = $DB->count_records_select('files', " filename != '' ");

        $monthlap = time() - (WEEKSECS * 4);
        $weeklap = time() - WEEKSECS;
        $daylap = time() - DAYSECS;
        $onlinelap = time() - (MINSECS * 5);

        $usercounters = new \StdClass;
        $usercounters->deleted = $DB->count_records('user', array('deleted' => 1));
        $usercounters->suspended = $DB->count_records('user', array('suspended' => 1));
        $usercounters->connected = 0;
        $usercounters->week = 0;
        $usercounters->day = 0;
        $usercounters->active = 0;

        $rs = $DB->get_recordset('user', array('suspended' => 0, 'deleted' => 0), 'id', 'id,lastlogin,firstaccess,lastaccess');
        foreach ($rs as $u) {
            $usercounters->active++;
            if ($u->firstaccess > 0) {
                $usercounters->connected++;
            }
            if ($u->lastlogin > $weeklap) {
                $usercounters->week++;
            }
            if ($u->lastlogin > $daylap) {
                $usercounters->day++;
            }
        }
        $rs->close();

        $stats->usercounters = $usercounters;

        $sql = "
            SELECT
                SUM(CASE WHEN visible = 1 THEN 1 ELSE 0 END) as visible,
                SUM(CASE WHEN c.startdate > ".time()." THEN 1 ELSE 0 END) as future
            FROM
                {course} c
        ";

        $stats->coursecounters = $DB->get_record_sql($sql, array($monthlap));

        set_config('sitestats', serialize($stats), 'local_my');
    }
}