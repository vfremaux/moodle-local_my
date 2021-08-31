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

class my_recent_courses_module extends module {

    public function __construct() {
        $this->area = 'recent_courses';
        $this->modulename = get_string('myrecentcourses', 'local_my');
    }

    public function get_courses() {
        global $USER, $DB;

        $logstoreinfo = local_my_get_logstore_info();

        $sql = "
            SELECT DISTINCT
                c.id,
                MAX(l.{$logstoreinfo->timeparam}) as lastping,
                c.shortname,
                c.fullname,
                c.visible,
                c.summary,
                c.summaryformat
            FROM
                {course} c,
                {{$logstoreinfo->table}} l
            WHERE
                l.{$logstoreinfo->courseparam} = c.id AND
                l.userid = ?
            GROUP BY
                c.id,
                c.shortname,
                c.fullname
            ORDER BY
                lastping DESC
            LIMIT 5
        ";

        $this->courses = $DB->get_records_sql($sql, [$USER->id]);

        $this->process_excluded();
        $this->process_metas();
        $this->process_courseareas();
    }

    protected function has_content($template) {
        return !empty($this->courses);
    }
}