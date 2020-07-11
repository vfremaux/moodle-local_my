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
require_once($CFG->dirroot.'/local/my/compatlib.php');

/**
 * This is part of the dual release distribution system.
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 */
function local_my_supports_feature($feature = null) {
    global $CFG;
    static $supports;

    $config = get_config('local_courseindex');

    if (!isset($supports)) {
        $supports = [
            'pro' => [
                'widgets' => ['extended', 'indicators'],
            ],
            'community' => [],
        ];
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    if (empty($feature)) {
        // Just return version.
        return $versionkey;
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    return $versionkey;
}

/**
 * This is a relocalized function in order to get local_my more compact.
 * checks if a user has a some named capability effective somewhere in a course.
 * @param string $capability
 * @param bool $excludesystem
 * @param bool $excludesite
 * @param bool $doanything
 * @param string $contextlevels restrict to some contextlevel may speedup the query.
 */
function local_my_has_capability_somewhere($capability, $excludesystem = true, $excludesite = true,
                                           $doanything = false, $contextlevels = '', $checkvisible = false) {
    global $USER, $DB;

    if (empty($contextlevels)) {
        $contextlevels = [CONTEXT_COURSE, CONTEXT_COURSECAT];
    } else {
        $contextlevels = explode(',', $contextlevels);
    }

    $params['capability'] = $capability;
    $params['userid'] = $USER->id;

    if (in_array(CONTEXT_COURSE, $contextlevels)) {

        $sitecontextexclclause = '';
        if ($excludesite) {
            $sitecontextexclclause = " ctx.id != 1  AND ";
        }

        $coursecheckvisibleclause = '';
        if ($checkvisible) {
            $coursecheckvisibleclause = " c.visible = 1 AND ";
        }

        // This is a a quick rough query that may not handle all role override possibility.

        $sql = "
            SELECT
                COUNT(DISTINCT ra.id)
            FROM
                {role_capabilities} rc,
                {role_assignments} ra,
                {context} ctx,
                {course} c
            WHERE
                rc.roleid = ra.roleid AND
                ra.contextid = ctx.id AND
                $sitecontextexclclause
                rc.capability = :capability AND
                ctx.contextlevel = ".CONTEXT_COURSE." AND
                ctx.instanceid = c.id AND
                $coursecheckvisibleclause
                ra.userid = :userid AND
                rc.permission = 1
        ";
        $hassomecourses = $DB->count_records_sql($sql, $params);
    }

   if (in_array(CONTEXT_COURSECAT, $contextlevels)) {

        $sitecontextexclclause = '';
        if ($excludesite) {
            $sitecontextexclclause = " ctx.id != 1  AND ";
        }

        $coursecatcheckvisibleclause = '';
        if ($checkvisible) {
            $coursecatcheckvisibleclause = " cc.visible = 1 AND ";
        }

        // This is a a quick rough query that may not handle all role override possibility.

        $sql = "
            SELECT
                COUNT(DISTINCT ra.id)
            FROM
                {role_capabilities} rc,
                {role_assignments} ra,
                {context} ctx,
                {course_categories} cc
            WHERE
                rc.roleid = ra.roleid AND
                ra.contextid = ctx.id AND
                $sitecontextexclclause
                rc.capability = :capability AND
                ctx.contextlevel = ".CONTEXT_COURSECAT." AND
                ctx.instanceid = cc.id AND
                $coursecatcheckvisibleclause
                ra.userid = :userid AND
                rc.permission = 1
        ";
        $hassomecategories = $DB->count_records_sql($sql, $params);
    }

    if (!empty($hassomecourses) || !empty($hassomecategories)) {
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

function local_my_get_available_courses(&$debuginfo, &$excludedcourses) {

    $config = get_config('local_my');

    $availablecourses = local_get_enrollable_courses();
    if (empty($availablecourses)) {
        return [];
    }

    $overcount = 0;
    if (!empty($config->maxavailablelistsize)) {
        $overcount = (count($availablecourses) > $config->maxavailablelistsize);
        if ($overcount) {
            $availablecourses = array_slice($availablecourses, 0, 11);
        }
    }

    $debuginfo .= local_my_process_excluded($excludedcourses, $availablecourses);

    return $availablecourses;
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

function local_get_cat_branch_rec($categoryid) {
    global $DB;

    $ids = local_get_cat_branch_ids_rec($categoryid);

    $catlist = [];
    foreach ($ids as $cid) {
        $cat = $DB->get_record('course_categories', ['id' => $cid]);
        $name = $cat->name;
        while (!empty($cat->parent)) {
            $cat = $DB->get_record('course_categories', ['id' => $cat->parent]);
            $name = $cat->name.' / '.$name;
        }
        $catlist[$cid] = $name;
    }

    return $catlist;
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
    if (mb_strlen($str) <= $n) {
        return $str;
    }

    $out = "";
    $small = mb_substr($str, 0, $n);
    $out = $small.$endchar;
    return $out;
}

/**
 * Cut the Course content by words.
 *
 * @param $str input string
 * @param $n number of words max
 * @param $endchar unfinished string suffix
 * @return the shortened string
 */
function local_my_course_trim_words($str, $w = 10, $endchar = '...') {

    // Preformatting.
    $str = str_replace(array("\r\n", "\r", "\n"), ' ', $str); // Remove all endlines
    $str = preg_replace('/\s+/', ' ', $str); // Reduce spaces.

    $words = explode(' ', $str);

    if (count($words) <= $w) {
        return $str;
    }

    $shortened = array_slice($words, 0, $w);
    $out = implode(' ', $shortened).' '.$endchar;
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

function local_my_scalar_array_merge(&$arr1, &$arr2) {

    if (empty($arr2)) {
        return;
    }
    foreach ($arr2 as $val) {
        if (!in_array($val, $arr1)) {
            $arr1[] = ''.$val;
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

function local_my_is_panel_empty($panelname) {

    $config = get_config('local_my');

    if (!isset($config->$panelname)) {
        throw new Exception('Invalid panel identifier '.$panelname);
    }

    if (empty($config->$panelname)) {
        // Quick path.
        return true;
    }

    $entries = explode("\n", $config->$panelname);

    foreach ($entries as $entry) {
        $entry = trim($entry);
        if (strpos($entry, '#') !== 0) {
            return false;
        }
    }

    return true;
}

/**
 * Helper to find the appropriate image for course when it can be dispayed.
 */
function local_my_get_image_url($imgname) {
    global $PAGE, $OUTPUT;

    $fs = get_file_storage();

    $context = context_system::instance();

    $haslocalfile = false;
    $frec = new StdClass;
    $frec->contextid = $context->id;
    $frec->component = 'local_my';
    $frec->filearea = 'rendererimages';
    $frec->filename = $imgname.'.svg';
    if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
        $frec->filename = $imgname.'.png';
        if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
            $frec->filename = $imgname.'.jpg';
            if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                $frec->filename = $imgname.'.gif';
                if ($fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                    $haslocalfile = true;
                }
            } else {
                $haslocalfile = true;
            }
        } else {
            $haslocalfile = true;
        }
    } else {
        $haslocalfile = true;
    }

    if ($haslocalfile) {
        $fileurl = moodle_url::make_pluginfile_url($frec->contextid, $frec->component, $frec->filearea, 0, '/',
                                                $frec->filename, false);
        return $fileurl;
    }

    if ($PAGE->theme->resolve_image_location($imgname, 'theme', true)) {
        $imgurl = $OUTPUT->image_url($imgname, 'theme');
    } else {
        return $OUTPUT->image_url($imgname, 'local_my');
    }

    return $imgurl;
}

/**
 * checks a course is favorite in user's preferences
 * @param int $courseid
 */
function local_my_is_favorite($courseid) {
    global $USER, $DB;

    $favorites = $DB->get_record('user_preferences', ['userid' => $USER->id, 'name' => 'local_my_favorite_courses']);
    if (!$favorites) {
        return false;
    }
    $arr = explode(',', $favorites);
    return in_array($courseid, $arr);
}

/**
 * Append a local my favorite course to user's preferences
 * @param int $courseid
 */
function local_my_add_to_favorites($courseid) {
    global $USER, $DB;

    $favorites = $DB->get_record('user_preferences', ['userid' => $USER->id, 'name' => 'local_my_favorite_courses']);
    if (!$favorites) {
        $favorites = new StdClass;
        $favorites->userid = $USER->id;
        $favorites->name = 'local_my_favorite_courses';
        $favorites->value = $courseid;
        $DB->insert_record('user_preferences', $favorites);
        return;
    }
    $favoriteids = explode(',', $favorites->value);
    if (!in_array($courseid, $favoriteids)) {
        $favoriteids[] = $courseid;
    }
    $favorites->value = implode(',', $favoriteids);
    $DB->update_record('user_preferences', $favorites);
}

/**
 * Removes a local my favorite course from user's preferences
 * @param int $courseid
 */
function local_my_remove_from_favorites($courseid) {
    global $USER, $DB;

    $favorites = $DB->get_field('user_preferences', 'value', ['userid' => $USER->id, 'name' => 'local_my_favorite_courses']);
    if (empty($favorites)) {
        return;
    }
    $favoritesids = explode(',', $favorites);
    $favarray = array_combine($favoritesids, $favoritesids);
    unset($favarray[$courseid]);
    $favorites = implode(',', array_keys($favarray));
    $DB->set_field('user_preferences', 'value', $favorites, ['userid' => $USER->id, 'name' => 'local_my_favorite_courses']);
}

/**
 * Checks if some course favorite widgets is used in any panel.
 * Result is cached in memory.
 */
function local_my_is_using_favorites() {
    static $using = null;

    if (is_null($using)) {

        $config = get_config('local_my');

        $panelnames = ['modules', 'teachermodules', 'coursemanagermodules', 'adminmodules'];

        $using = false;
        foreach ($panelnames as $panelname) {
            if (preg_match('/\\bmy_favorite_/', $config->$panelname)) {
                $using = true;
            }
        }
    }

    return $using;
}
