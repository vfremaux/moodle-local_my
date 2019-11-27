<?php

namespace local_my\module;

class available_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'available_courses';
        $this->modulename = get_string('availablecourses', 'local_my');
        $this->options['withanonymous'] = true;
        $this->options['noprogress'] = true;
    }

    public function get_courses() {
        global $DB, $USER;

        if (!empty($this->options['withanonymous'])) {
            $enroltypeclause = " (enrol = 'self' OR enrol = 'guest' OR enrol = 'profilefield' OR enrol = 'paypal') AND ";
        } else {
            $enroltypeclause = " (enrol = 'self' OR enrol = 'profilefield' OR enrol = 'paypal') AND ";
        }

        // Select all active enrols self or guest where i'm not enrolled in.
        $sql = "
            SELECT
                e.id,
                e.enrol,
                e.courseid as cid,
                e.customchar1,
                e.customchar2,
                e.customint5 as cohortbinding
            FROM
                {enrol} e
            LEFT JOIN
                {user_enrolments} ue
            ON
                ue.userid = ? AND
                ue.enrolid = e.id
            WHERE
                e.status = 0 AND
                $enroltypeclause
                ue.id IS NULL
        ";
        $possibles = $DB->get_records_sql($sql, array($USER->id));

        $sql = "
            SELECT DISTINCT
                e.courseid as id,
                e.courseid as cid
            FROM
                {enrol} e,
                {user_enrolments} ue
            WHERE
                ue.userid = ? AND
                ue.enrolid = e.id AND
                e.status = 0 AND
                ue.status = 0 AND
                e.enrol != 'guest'
        ";
        $actives = $DB->get_records_sql($sql, array($USER->id));

        // Collect unique list of possible courses.
        if (!empty($possibles)) {
            foreach ($possibles as $e) {

                $pass = 0;
                $nopass = 0;

                // Check cohort restriction.
                if ($e->enrol == 'self') {
                    if ($e->cohortbinding) {
                        $params = array('cohortid' => $e->cohortbinding, 'userid' => $USER->id);
                        if (!$DB->record_exists('cohort_members', $params)) {
                            $nopass++;
                        } else {
                            $pass++;
                        }
                    } else {
                        $pass++;
                    }
                }

                if ($e->enrol == 'profilefield') {
                    $enrol = enrol_get_plugin('profilefield');
                    // If profile not matching and a profile enrol is required, discard.
                    if ($enrol->check_user_profile_conditions($e)) {
                        $pass++;
                    } else {
                        $nopass++;
                    }
                }

                if (!$pass && $nopass) {
                    // If none is passing, but one at least retriction method fired, then discard.
                    continue;
                }

                if (!array_key_exists($e->cid, $this->courses)) {
                    $fields = 'id,shortname,fullname,visible,summary,sortorder,category';
                    $this->courses[$e->cid] = $DB->get_record('course', array('id' => $e->cid), $fields);
                    $params = array('id' => $this->courses[$e->cid]->category);
                    $this->courses[$e->cid]->ccsortorder = $DB->get_field('course_categories', 'sortorder', $params);
                }
            }
        }

        // Filter out already enrolled.
        if (!empty($actives) && !empty($this->courses)) {
            foreach ($actives as $a) {
                if (array_key_exists($a->id, $this->courses)) {
                    unset($this->courses[$a->id]);
                }
            }
        }

        if (!empty($this->courses)) {
            uasort($this->courses, 'local_sort_by_ccc');
        }

        $this->process_excluded();
        $this->process_metas();
        $this->process_courseareas();
    }
}