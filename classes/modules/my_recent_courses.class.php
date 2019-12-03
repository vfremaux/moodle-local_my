<?php

namespace local_my\module;

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