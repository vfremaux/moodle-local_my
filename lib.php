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
if (!defined('MOODLE_EARLY_INTERNAL')) {
    defined('MOODLE_INTERNAL') || die();
}

require_once($CFG->dirroot.'/local/my/modules.php');

/**
 * This function is not implemented in thos plugin, but is needed to mark
 * the vf documentation custom volume availability.
 */
function local_my_supports_feature() {
    assert(1);
}

/**
 * This is a relocalized function in order to get local_my more compact.
 * checks if a user has a some named capability effective somewhere in a course.
 * @param string $capability;
 * @param bool $excludesystem
 * @param bool $excludesite
 * @param bool $doanything
 * @param string $contextlevels restrict to some contextlevel may speedup the query.
 */
function local_my_has_capability_somewhere($capability, $excludesystem = true, $excludesite = true,
                                           $doanything = false, $contextlevels = '') {
    global $USER, $DB;

    $contextclause = '';

    if ($contextlevels) {
        list($sql, $params) = $DB->get_in_or_equal(explode(',', $contextlevels), SQL_PARAMS_NAMED);
        $contextclause = "
           AND ctx.contextlevel $sql
        ";
    }
    $params['capability'] = $capability;
    $params['userid'] = $USER->id;

    $sitecontextexclclause = '';
    if ($excludesite) {
        $sitecontextexclclause = " ctx.id != 1  AND ";
    }

    // This is a a quick rough query that may not handle all role override possibility.

    $sql = "
        SELECT
            COUNT(DISTINCT ra.id)
        FROM
            {role_capabilities} rc,
            {role_assignments} ra,
            {context} ctx
        WHERE
            rc.roleid = ra.roleid AND
            ra.contextid = ctx.id AND
            $sitecontextexclclause
            rc.capability = :capability
            $contextclause
            AND ra.userid = :userid AND
            rc.permission = 1
    ";
    $hassome = $DB->count_records_sql($sql, $params);

    if (!empty($hassome)) {
        return true;
    }

    $systemcontext = context_system::instance();
    if (!$excludesystem && has_capability($capability, $systemcontext, $USER->id, $doanything)) {
        return true;
    }

    return false;
}

/**
 * checks if a user has a myoverride capability somewhere, so he might be My Moodle
 * exampted.
 */
function local_has_myoverride_somewhere() {
    global $USER, $CFG;

    // TODO : explore caps for a moodle/local:overridemy positive answer.
    if ($hassome = local_my_has_capability_somewhere('local/my:overridemy', false, false, true,
                                                  CONTEXT_COURSE.','.CONTEXT_COURSECAT.','.CONTEXT_SYSTEM)) {
        return true;
    }

    /*
     * ADDED : on special configuration check positive response of an override driver
     * that could come from having some profile field marked
     */
    if (@$CFG->specialprofilefieldmyoverridedrivers) {

        $drivers = "'".str_replace(',', "','", $CFG->specialprofilefieldmyoverridedrivers)."'";

        if ($myprofiledriverfields = $DB->get_records_select('user_info_field', " shortname IN ('$drivers') ")) {
            foreach ($myprofiledriverfields as $f) {
                if ($driverdata = $DB->get_record('user_info_data', array('fieldid' => $f->id, 'userid' => $USER->id))) {
                    if ($driverdata->data == 1) {
                        return true;
                    }
                }
            }
        }
    }

    return false;
}

function local_my_before_footer() {
    global $PAGE, $USER;

    $config = get_config('local_my');

    $systemcontext = context_system::instance();
    if (!empty($config->force) && !has_capability('local/my:overridemy', $systemcontext, $USER, false)) {
        $PAGE->requires->js_call_amd('local_my/local_my', 'hide_home_nav', [null]);
    }
}

function local_my_fetch_modules($view) {
    $config = get_config('local_my');

    $mymodules = array();
    $myleftmodules = array();

    switch ($view) {
        case 'asteacher';
            $modgroup = 'teachermodules';
            break;

        case 'ascoursemanager':
            $modgroup = 'coursemanagermodules';
            break;

        case 'asadmin':
            $modgroup = 'adminmodules';
            break;

        default:
            $modgroup = 'modules';
    }

    if ($config->$modgroup) {

        $modules = preg_split("/[\\n,]|\\s+/", $config->$modgroup);

        for ($i = 0; $i < count($modules); $i++) {
            $module = trim($modules[$i]);
            $modules[$i] = $module; // Store it back into full modules list.
            if (preg_match('/-L$/', $module)) {
                $myleftmodules[$i] = preg_replace('/-L$/', '', $module);
            } else {
                // In case it has been explicitely right-located (default).
                $mymodules[$i] = preg_replace('/-R$/', '', $module);
            }
        }
    }

    return array($modules, $mymodules, $myleftmodules);
}

/**
 * Variants to get_my_courses
 * get all courses that are non meta
 * @param array a cache for courses
 */
function local_get_my_main_courses(&$courses = null) {
    global $USER;

    if (is_null($courses)) {
        $courses = enrol_get_my_courses($USER->id, 'visible DESC,sortorder ASC', '*', false);
    }
    $maincourses = array();

    if (!empty($courses)) {
        foreach ($courses as $c) {
            if (!local_my_is_meta($c)) {
                $maincourses[$c->id] = $c;
            }
        }
    }
    return $maincourses;
}

/**
 * Variants to get_my_courses
 * get all courses that are meta (learning modules)
 * If certificate module is installed, the certification status is checked
 * @param array a cache for courses
 * @param boolean certified
 */
function local_get_my_meta_courses(&$courses = null, $certified = 0) {
    global $USER, $DB;

    if (is_null($courses)) {
        $courses = enrol_get_my_courses('*', 'visible DESC, sortorder ASC', false);
    }
    $metacourses = array();

    $certinstalled = $DB->get_record('modules', array('name' => 'certificate'));

    if (!empty($courses)) {
        foreach ($courses as $c) {

            // Check for course certificate (a user is certified if all certificates in course are issued).
            if ($certinstalled) {
                $coursehascert = true;
                if ($certs = $DB->get_records('certificate', array('course' => $c->id))) {
                    foreach ($certs as $cert) {
                        $params = array('userid' => $USER->id, 'certificateid' => $cert->id);
                        if (!$DB->record_exists('certificate_issues', $params)) {
                            $coursehascert = false;
                            break;
                        }
                    }
                } else {
                    $coursehascert = false;
                }
            }

            if (local_my_is_meta($c) &&
                    (!$certinstalled ||
                            ((($coursehascert && $certified) ||
                                    (!$coursehascert && !$certified))))) {
                $metacourses[$c->id] = $c;
            }
        }
    }
    return $metacourses;
}

/**
 * get courses i am authoring in (or by capability).
 *
 */
function local_get_my_authoring_courses($fields = '*', $capability = 'local/my:isauthor') {
    global $USER, $DB, $CFG;

    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    $authoredcourses = array();
    $authored = local_get_user_capability_course($capability, $USER->id, false, '', 'cc.sortorder, c.sortorder');
    if ($authored) {
        foreach ($authored as $a) {
            $context = context_course::instance($a->id);
            if (!has_capability('local/my:iscoursemanager', $context, $USER, false)) {
                // doanything not considered here.
                $authoredcourses[$a->id] = $DB->get_record('course', array('id' => $a->id), $fields);
                if ($debug) {
                    echo "Accept {$a->id} by capability $capability<br/>\n";
                }
            } else {
                if ($debug) {
                    echo "Reject {$a->id} because coursemanager<br/>\n";
                }
            }
        }
        return $authoredcourses;
    }
    return array();
}

/**
 * get courses i am managing (or by capability).
 *
 */
function local_get_my_managed_courses($fields = '*', $capability = 'local/my:iscoursemanager') {
    global $USER, $DB;

    if ($managed = local_get_user_capability_course($capability, $USER->id, false, '', 'cc.sortorder, c.sortorder')) {
        foreach ($managed as $a) {
            $managedcourses[$a->id] = $DB->get_record('course', array('id' => $a->id), $fields);
        }
        return $managedcourses;
    }
    return array();
}

/**
 * get courses templates i am authoring in.
 * @return an array of course records.
 */
function local_get_my_templates() {
    global $USER, $DB, $CFG;

    require_once($CFG->dirroot.'/local/coursetemplates/xlib.php');

    $config = get_config('local_coursetemplates');

    $templatecatids = local_coursetemplates_get_template_categories();

    $templatecourses = array();
    if ($templates = local_get_user_capability_course('local/my:isauthor', $USER->id, false, '', 'cc.sortorder, c.sortorder')) {
        foreach ($templates as $t) {
            $category = $DB->get_field('course', 'category', array('id' => $t->id));
            if (in_array($category, $templatecatids)) {
                $templatecourses[$t->id] = $DB->get_record('course', array('id' => $t->id));
            }
        }
        return $templatecourses;
    }
    return array();
}

/**
 * get courses the current user can enrol in.
 *
 */
function local_get_enrollable_courses($withanonymous = true) {
    global $DB, $USER;

    if ($withanonymous) {
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
    $courses = array();
    if (!empty($possibles)) {
        $courseids = array();

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

            if (!in_array($e->cid, $courseids)) {
                $fields = 'id,shortname,fullname,visible,summary,sortorder,category';
                $courses[$e->cid] = $DB->get_record('course', array('id' => $e->cid), $fields);
                $params = array('id' => $courses[$e->cid]->category);
                $courses[$e->cid]->ccsortorder = $DB->get_field('course_categories', 'sortorder', $params);
                $courseids[] = $e->cid;
            }
        }
    }

    // Filter out already enrolled.
    if (!empty($actives) && !empty($courses)) {
        foreach ($actives as $a) {
            if (array_key_exists($a->id, $courses)) {
                unset($courses[$a->id]);
            }
        }
    }

    if (!empty($courses)) {
        uasort($courses, 'local_sort_by_ccc');
    }

    return $courses;
}

function local_sort_by_ccc($a, $b) {
    if ($a->ccsortorder * 10000 + $a->sortorder > $b->ccsortorder * 10000 + $b->sortorder) {
        return 1;
    } else if ($a->ccsortorder * 10000 + $a->sortorder < $b->ccsortorder * 10000 + $b->sortorder) {
        return -1;
    }
    return 0;
}

/**
 * Check if a course is a metacourse (new way)
 * @param object $c the course
 * @param int $userid if NULL, no check of user actual enrollement, if 0, use current USER id to check.
 */
function local_my_is_meta(&$c, $userid = 0) {
    global $DB, $USER;

    $now = time();
    $datesql = "
        ($now >= enrolstartdate AND
        enrolenddate = 0) OR
        (enrolstartdate = 0 AND
        $now <= enrolenddate) OR
        (enrolstartdate = 0 AND
        enrolenddate = 0) OR
        ($now >= enrolstartdate AND
        $now <= enrolenddate)
    ";

    $uedatesql = "
        ($now >= timestart AND
        timeend = 0) OR
        (timestart = 0 AND
        $now <= timeend) OR
        (timestart = 0 AND
        timeend = 0) OR
        ($now >= timestart AND
        $now <= timeend)
    ";

    $select = "
        enrol = 'meta' AND
        courseid = ? AND
        ($datesql)
    ";
    if ($metaenrols = $DB->get_records_select('enrol', $select, array($c->id))) {
        if (is_null($userid)) {
            return true;
        } else {
            $uid = ($userid === 0) ? $USER->id : $userid;
            foreach ($metaenrols as $me) {
                $select = "
                    userid = ? AND
                    enrolid = ? AND
                    ($uedatesql) ";
                if ($DB->record_exists_select('user_enrolments', $select, array($uid, $me->id))) {
                    return true;
                }
            }
        }
    }
    return false;
}

function local_my_print_courses($title = 'mycourses', $courses, $options = array()) {
    global $OUTPUT, $DB, $PAGE;

    $config = get_config('local_my');
    $renderer = $PAGE->get_renderer('local_my');

    $str = '';

    // Be sure we have something in lastaccess.
    foreach ($courses as $cid => $c) {
        $courses[$cid]->lastaccess = 0 + @$courses[$cid]->lastaccess;
    }

    if (empty($courses)) {
        if (!empty($options['printifempty']) && empty($options['noheading'])) {
            $str .= $OUTPUT->box_start('header');
            $str .= $OUTPUT->box_start('title');
            $str .= '<h2>'.get_string($title, 'local_my').'</h2>';
            $str .= $OUTPUT->box_end();
            $str .= $OUTPUT->box_end();
            $str .= $OUTPUT->box(get_string('nocourses', 'local_my'), 'content');
        }
    } else {
        if (empty($options['noheading'])) {
            $str .= $OUTPUT->box_start('header');
            $str .= $OUTPUT->box_start('title');
            $str .= '<h2>'.get_string($title, 'local_my').'</h2>';
            $str .= $OUTPUT->box_end();
            $str .= $OUTPUT->box_end();
            $str .= $OUTPUT->box_start('content');
        }

        $str .= '<table class="courselist" width="100%">';
        if (!empty($options['withoverview'])) {
            $str .= $renderer->course_overview($courses, $options);
        } else if (!empty($options['withcats'])) {
            $str .= $renderer->courses_by_cats($courses, $options, $title);
        } else {
            foreach ($courses as $c) {
                $c->idnumber = $DB->get_field('course', 'idnumber', array('id' => $c->id));
                $str .= $renderer->course_table_row($c, $options);
            }
        }
        $str .= '</table>';

        if (empty($options['noheading'])) {
            $str .= $OUTPUT->box_end();
        }
    }

    return $str;
}

/**
 * returns a context in which the user can do restore.
 */
function local_get_one_of_my_power_contexts() {

    if ($courseswithbackup = get_user_capability_course('moodle/restore:restorecourse')) {
        $oneof = array_shift($courseswithbackup);
        return context_course::instance($oneof->id);
    }
    return null;
}

/**
 * fetches all categories in an branch
 *
 */
function local_get_cat_branch_ids_rec($categoryid) {
    global $DB;

    $catids = array($categoryid);
    if ($subs = $DB->get_records('course_categories', array('parent' => $categoryid), 'sortorder', 'id,parent')) {
        foreach ($subs as $cid => $foo) {
            $catids = array_merge($catids, local_get_cat_branch_ids_rec($cid));
        }
    }

    return $catids;
}

/**
 * Prefetch courses that will be printed by course areas
 */
function local_prefetch_course_areas(&$excludedcourses) {
    global $DB;

    $allmycourses = enrol_get_my_courses('id, shortname');
    $config = get_config('local_my');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            unset($allmycourses[$id]);
        }
    }

    if (empty($config->courseareas) && empty($config->courseareas2)) {
        // Performance quick trap.
        return array();
    }

    $prefetchareacourses = array();

    // Get the first coursearea zone exclusions.
    for ($i = 0; $i < $config->courseareas; $i++) {

        $coursearea = 'coursearea'.$i;
        if (!empty($config->$coursearea)) {
            $mastercategory = $DB->get_record('course_categories', array('id' => $config->$coursearea));
            if ($mastercategory) {
                // Filter courses of this area.
                $retainedcategories = local_get_cat_branch_ids_rec($mastercategory->id);
                foreach ($allmycourses as $c) {
                    if (in_array($c->category, $retainedcategories)) {
                        $c->summary = $DB->get_field('course', 'summary', array('id' => $c->id));
                        $prefetchareacourses[$c->id] = $c;
                    }
                }
            }
        }
    }

    // Add the second coursearea zone exclusions.
    for ($i = 0; $i < $config->courseareas2; $i++) {

        $coursearea = 'coursearea2_'.$i;
        if (!empty($config->$coursearea)) {
            $mastercategory = $DB->get_record('course_categories', array('id' => $config->$coursearea));
            if ($mastercategory) {
                // Filter courses of this area.
                $retainedcategories = local_get_cat_branch_ids_rec($mastercategory->id);
                foreach ($allmycourses as $c) {
                    if (in_array($c->category, $retainedcategories)) {
                        $c->summary = $DB->get_field('course', 'summary', array('id' => $c->id));
                        $prefetchareacourses[$c->id] = $c;
                    }
                }
            }
        }
    }

    return $prefetchareacourses;
}

function local_my_hide_home() {

    $config = get_config('local_my');

    if ($config->enable) {
        return false;
    }

    if ($config->force) {
        if (local_has_myoverride_somewhere()) {
            return false;
        }
        return true;
    }
}

/**
 * This function clones the accesslib.php function get_user_capability_course, and gets the list
 * of courses that this user has a particular capability in. the difference resides in that we look
 * only for direct assignations here and not on propagated authorisations.
 * It is still not very efficient.
 *
 * @param string $capability Capability in question
 * @param int $userid User ID or null for current user
 * @param bool $doanything True if 'doanything' is permitted (default)
 * @param string $fieldsexceptid Leave blank if you only need 'id' in the course records;
 *   otherwise use a comma-separated list of the fields you require, not including id
 * @param string $orderby If set, use a comma-separated list of fields from course
 *   table with sql modifiers (DESC) if needed
 * @return array|bool Array of courses, if none found false is returned.
 */
function local_get_user_capability_course($capability, $userid = null, $doanything = true, $fieldsexceptid = '',
                                          $orderby = '') {
    global $DB, $CFG;

    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    // Convert fields list and ordering.
    $fieldlist = '';
    if ($fieldsexceptid) {
        $fields = explode(',', $fieldsexceptid);
        foreach ($fields as $field) {
            $fieldlist .= ',c.'.$field;
        }
    }
    if ($orderby) {
        $fields = explode(',', $orderby);
        $orderby = '';
        foreach ($fields as $field) {
            if ($orderby) {
                $orderby .= ',';
            }
            $orderby .= $field;
        }
        $orderby = 'ORDER BY '.$orderby;
    }

    /* Obtain a list of everything relevant about all courses including context but
     * only where user has roles directly inside.
     * Note the result can be used directly as a context (we are going to), the course
     * fields are just appended.
     */

    $contextpreload = context_helper::get_preload_record_columns_sql('x');

    $courses = array();

    $sql = "
        SELECT
            c.id
            $fieldlist,
            $contextpreload
        FROM
            {course} c
        JOIN
            {context} x
        ON
            (c.id=x.instanceid AND x.contextlevel=".CONTEXT_COURSE.")
        JOIN
            {role_assignments} ra
        ON
            (ra.contextid = x.id AND ra.userid = ?)
        JOIN
            {course_categories} cc
        ON
            cc.id = c.category
        GROUP BY
            c.id

        $orderby";

    $rs = $DB->get_recordset_sql($sql, array($userid));

    // Check capability for each course in turn.
    foreach ($rs as $course) {
        $context = context_course::instance($course->id);
        if (has_capability($capability, $context, $userid, $doanything)) {
            /*
             * We've got the capability. Make the record look like a course record
             * and store it
             */
            $courses[$course->id] = $course;
            if ($debug) {
                echo "Catched {$course->id} by query on $capability<br/>\n";
            }
        } else {
            if ($debug) {
                echo "Rejected {$course->id} by capability $capability<br/>\n";
            }
        }
    }
    $rs->close();
    return $courses;
}

function local_my_is_meta_for_user($courseid, $userid) {
    global $DB;

    $sql = "
        SELECT
            SUM(CASE WHEN e.enrol = 'meta' THEN 1 ELSE 0 END) as metas,
            SUM(CASE WHEN e.enrol <> 'meta' THEN 1 ELSE 0 END) as nonmetas
        FROM
            {enrol} e,
            {user_enrolments} ue
        WHERE
            e.id = ue.enrolid AND
            ue.userid = ? AND
            e.status = 0 AND
            e.courseid = ? AND
            ue.status = 0
    ";
    $metainfo = $DB->get_record_sql($sql, array($userid, $courseid));
    if ($metainfo->metas > 0 && !$metainfo->nonmetas) {
        return true;
    }
    return false;
}

function local_my_get_logstore_info() {

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers('\core\log\sql_reader');
    $reader = reset($readers);

    if (empty($reader)) {
        return false;
    }

    $logstoreinfo = new StdClass;
    if ($reader instanceof \logstore_standard\log\store) {
        $logstoreinfo->table = 'logstore_standard_log';
        $logstoreinfo->courseparam = 'courseid';
        $logstoreinfo->timeparam = 'timecreated';
    } else if ($reader instanceof \logstore_legacy\log\store) {
        $logstoreinfo->table = 'log';
        $logstoreinfo->courseparam = 'course';
        $logstoreinfo->timeparam = 'time';
    } else {
        $logstoreinfo->table = 'logstore_standard_log';
        $logstoreinfo->courseparam = 'courseid';
        $logstoreinfo->timeparam = 'timecreated';
    }
    return $logstoreinfo;
}

function local_my_strip_html_tags($text) {
    $text = preg_replace(
        array(
            // Remove invisible content.
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
            // Add line breaks before and after blocks.
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0",
        ),
        $text
    );
    return strip_tags( $text );
}

/**
 * Cut the Course content.
 *
 * @param $str
 * @param $n
 * @param $end_char
 * @return string
 */
function local_my_course_trim_char($str, $n = 500, $endchar = '...') {
    if (strlen($str) < $n) {
        return $str;
    }

    $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));
    if (strlen($str) <= $n) {
        return $str;
    }

    $out = "";
    $small = substr($str, 0, $n);
    $out = $small.$endchar;
    return $out;
}

/**
 * Serves the format page context (course context) attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function local_my_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {

    require_course_login($course);

    $fileareas = array('rendererimages');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $context = context_system::instance();

    $pageid = (int) array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_my/$filearea/$pageid/$relativepath";
    if ((!$file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        echo "Out not found";
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true); // Download MUST be forced - security!
}

/**
 * renders an extended page my module.
 */
function local_my_render_module($m, &$excludedcourses, &$courseareacourses) {
    global $PAGE;

    $m = trim($m);
    if (empty($m) || preg_match('/^\s+$/', $m)) {
        return; // Blank lines.
    }
    if (preg_match('/^[!_*#]/', $m)) {
        return; // Ignore some modules.
    }
    if ($m == 'my_caption' || $m == 'left_edition_column') {
        return; // Special cases.
    }

    // Special case : print a block replica.
    if (preg_match('/block_(\d+)$/', $m, $matches)) {
        $fname = 'local_my_print_block';
        echo $fname($matches[1], $PAGE->context->id);
        return;
    }

    // Special case : print statics can be freely indexed.
    if (preg_match('/static_(.*)$/', $m, $matches)) {
        $fname = 'local_my_print_static';
        echo $fname($matches[1]);
        return;
    }

    $fname = 'local_my_print_'.$m;
    if (!function_exists($fname)) {
        echo get_string('unknownmodule', 'local_my', $fname).'<br/>';
    } else {
        echo $fname($excludedcourses, $courseareacourses);
    }
}

function local_my_scalar_array_merge(&$arr1, &$arr2) {

    if (empty($arr2)) {
        return;
    }
    foreach ($arr2 as $val) {
        if (!in_array($val, $arr1)) {
            $arr1[] = $val;
        }
        sort($arr1);
    }
}

function local_my_is_visible_course(&$course) {
    global $DB;

    if (!$course->visible) {
        return false;
    }

    if (!$course->category) {
        return true; // this is the SITE course.
    }

    $cat = $DB->get_record('course_categories', array('id' => $course->category));
    if (empty($cat->visible)) {
        return false;
    }

    while ($cat->parent) {
        $cat = $DB->get_record('course_categories', array('id' => $cat->parent));
        if (!$cat->visible) {
            return false;
        }
    }
    return true;
}

function local_my_is_selfenrolable_course($course) {
    global $DB;

    $params = array('courseid' => $course->id, 'enrol' => 'self', 'status' => 0);
    if ($DB->count_records('enrol', $params)) {
        return true;
    }
    return false;
}

function local_my_is_guestenrolable_course($course) {
    global $DB;

    $params = array('courseid' => $course->id, 'enrol' => 'guest', 'status' => 0);
    if ($DB->count_records('enrol', $params)) {
        return true;
    }
    return false;
}

function local_my_process_metas(&$courselist) {
    global $USER, $DB;

    $config = get_config('local_my');
    $debug = optional_param('debug', false, PARAM_BOOL);
    $debuginfo = '';

    foreach ($courselist as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    $debuginfo .= "reject meta $id as meta disabled";
                }
                unset($courselist[$id]);
                continue;
            }
        }
        $courselist[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    return $debuginfo;
}

function local_my_process_excluded($excludedcourses, &$courselist) {

    $debug = optional_param('debug', false, PARAM_BOOL);

    $debuginfo = '';
    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $cid) {
            if (!empty($cid)) {
                if ($debug) {
                    $debuginfo .= "rejected $cid as excluded</br/>";
                }
                unset($courselist[$cid]);
            }
        }
    }

    return $debuginfo;
}