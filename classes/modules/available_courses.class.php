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

class available_courses_module extends my_courses_module {

    public function __construct() {
        module::__construct();
        $this->area = 'available_courses';
        $this->modulename = get_string('availablecourses', 'local_my');
        $this->options['withanonymous'] = true;
        $this->options['incategories'] = null;
        $this->options['noprogress'] = true;
    }

    public function get_courses() {
        global $DB, $USER;

        if (!empty($this->options['withanonymous'])) {
            $enroltypeclause = " (enrol = 'self' OR enrol = 'guest' OR enrol = 'profilefield' OR enrol = 'paypal') AND ";
        } else {
            $enroltypeclause = " (enrol = 'self' OR enrol = 'profilefield' OR enrol = 'paypal') AND ";
        }

        $categoryclause = '';
        $inparams = [];
        if (!empty($this->options['incategories'])) {
            if (!is_array($this->options['incategories'])) {
                $catlist = explode(',', $this->options['incategories']);
            } else {
                $catlist = $this->options['incategories'];
            }
            self::add_debuginfo("Getting available courses in ".implode(', ', $catlist));

            list($insql, $inparams) = $DB->get_in_or_equal($catlist, SQL_PARAMS_NAMED);
            $categoryclause = " AND c.category $insql ";
        }
        $inparams['userid'] = $USER->id;

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
                {course} c,
                {enrol} e
            LEFT JOIN
                {user_enrolments} ue
            ON
                ue.userid = :userid AND
                ue.enrolid = e.id
            WHERE
                c.id = e.courseid AND
                e.status = 0 AND
                $enroltypeclause
                ue.id IS NULL
                {$categoryclause}
        ";
        $possibles = $DB->get_records_sql($sql, $inparams);

        $sql = "
            SELECT DISTINCT
                e.courseid as id,
                e.courseid as cid
            FROM
                {course} c,
                {enrol} e,
                {user_enrolments} ue
            WHERE
                c.id = e.courseid AND
                ue.userid = :userid AND
                ue.enrolid = e.id AND
                e.status = 0 AND
                ue.status = 0 AND
                e.enrol != 'guest'
                {$categoryclause}
        ";
        $actives = $DB->get_records_sql($sql, $inparams);

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
        if (empty($this->options['noexcludefromstream'])) {
            $this->process_excluded();
            $this->process_metas();
            $this->process_courseareas();
        }
    }
}