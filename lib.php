<?php

require_once($CFG->dirroot.'/local/my/modules.php');
require_once($CFG->dirroot.'/local/lib.php');

/**
 * checks if a user has a myoverride capability somewhere, so he might be My Moodle 
 * exampted.
 */
function local_has_myoverride_somewhere() {
    global $USER, $CFG;

    // TODO : explore caps for a moodle/local:overridemy positive answer.
    if ($hassome = local_has_capability_somewhere('local/my:overridemy', false, false, true)) {
        return true;
    }

    // ADDED : on special configuration check positive response of an override driver
    // that could come from having some profile field marked 
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
                        if (!$DB->record_exists('certificate_issues', array('userid' => $USER->id, 'certificateid' => $cert->id))) {
                            $coursehascert = false;
                            break;
                        }
                    }
                } else {
                    $coursehascert = false;
                }
            }

            if (local_my_is_meta($c) && (!$certinstalled || ((($coursehascert && $certified) || (!$coursehascert && !$certified))))) {
                $metacourses[$c->id] = $c;
            }
        }
    }
    return $metacourses;
}

/**
 * Print a simple list of coures with first level category caption
 */
function local_print_courses_by_cats($courselist, $options = array(), $return = false) {
    global $CFG, $DB;

    $str = '';

    // Reorganise by cat.
    foreach ($courselist as $c) {
        if (!isset($catcourses[$c->category])) {
            $catcourses[$c->category] = new StdClass;
            $catcourses[$c->category]->category = $DB->get_record('course_categories', array('id' => $c->category));
        }
        $catcourses[$c->category]->courses[] = $c;
    }

    foreach ($catcourses as $catid => $cat) {
        if ($catid) {
            $catcontext = context_coursecat::instance($catid);
            if ($cat->category->visible || has_capability('moodle/category:viewhiddencategories', $catcontext)) {
                $catstyle = ($cat->category->visible) ? '' : 'shadow' ;
                if ($options['withcats'] == 1) {
                    $str .= '<tr valign="top"><td class="'.$catstyle.'"><b>'.format_string($cat->category->name).'</b></td></tr>';
                } elseif ($options['withcats'] > 1) {
                    $cats = array();
                    $cats[] = format_string($cat->category->name);
                    if ($cat->category->parent) {
                        $parent = $cat->category;
                        for ($i = 1; $i < $options['withcats'] ; $i++) {
                            $parent = $DB->get_record('course_categories', array('id' => $parent->parent));
                            $cats[] = format_string($parent->name);
                        }
                    }
                    $cats = array_reverse($cats);
                    $str .= '<tr valign="top"><td class="'.$catstyle.'"><b>'.implode(' / ', $cats).'</b></td></tr>';
                }
                foreach ($cat->courses as $c) {
                    $coursecontext = context_course::instance($c->id);
                    if ($c->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                        $courseurl = new moodle_url('/course/view.php', array('id' => $c->id));
                        $cstyle = ($c->visible && empty($catstyle)) ? '' : 'shadow' ;
                        $str .= '<tr valign="top">';
                        $str .= '<td class="course">';
                        $str .= '<a class="'.$cstyle.'" href="'.$courseurl.'">'.format_string($c->fullname).'</a>';
                        $str .= '</td>';
                        $str .='</tr>';
                    }
                }
            }
        }
    }
    if ($return) return $str;
    echo $str;
}

/**
 * get courses i am authoring in.
 *
 */
function local_get_my_authoring_courses() {
    global $USER, $CFG, $DB;

    if ($authored = local_get_user_capability_course('moodle/course:manageactivities', $USER->id, false)) {
        foreach ($authored as $a) {
            $authoredcourses[$a->id] = $DB->get_record('course', array('id' => $a->id));
        }
        return $authoredcourses;
    }
    return array();
}

/**
 * get courses i am authoring in.
 *
 */
function local_get_my_templates() {
    global $USER, $CFG, $DB;

    $config = get_config('local_coursetemplates');

    $templatecourses = array();
    if ($templates = local_get_user_capability_course('moodle/course:manageactivities', $USER->id, false)) {
        foreach ($templates as $t) {
            $category = $DB->get_field('course', 'category', array('id' => $t->id));
            if ($category == $config->templatecategory) {
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
    global $CFG, $DB, $USER;

    if ($withanonymous) {
        $enroltypeclause = " AND (enrol = 'self' OR enrol = 'guest') ";
    } else {
        $enroltypeclause = " enrol = 'self' ";
    }

    $sql = "
        SELECT
            c.id, c.visible, c.fullname, c.shortname, c.category, c.summary,
            SUM(IF(ue.id IS NOT NULL, 1, 0)) as uecount
        FROM
            {course} c
        JOIN
            {course_categories} cc
        ON
            c.category = cc.id
        JOIN
            {enrol} e
        ON 
            e.courseid = c.id
            $enroltypeclause AND
            status = 0
        LEFT JOIN
            {user_enrolments} ue
        ON
            ue.userid = $USER->id AND
            ue.enrolid = e.id
        WHERE
            c.category = cc.id AND
            c.id != ".SITEID."
        GROUP BY 
            c.id, c.fullname, c.shortname, c.category
        HAVING 
            uecount = 0
        ORDER BY
            cc.sortorder, c.sortorder
    ";

    $courses = $DB->get_records_sql($sql);
    return $courses;
}

/**
 * Check if a course is a metacourse (new way)
 * @param object $c the course
 * @param int $userid if NULL, no check of user actual enrollement, if 0, use current USER id to check.
 */
function local_my_is_meta(&$c, $userid = 0) {
    global $CFG, $DB, $USER;

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
    
    if ($metaenrols = $DB->get_records_select('enrol', " enrol = 'meta' AND courseid = {$c->id} AND ($datesql) ")) {
        if (is_null($userid)) {
            return true;
        } else {
            $uid = ($userid === 0) ? $USER->id : $userid ;
            foreach ($metaenrols as $me) {
                if ($DB->record_exists_select('user_enrolments', " userid = $uid AND enrolid = {$me->id} AND ($uedatesql) ")) {
                    return true;
                }
            }
        }
    }
    return false;
}

function local_my_print_courses($title = 'mycourses', $courses, $options = array(), $return = true) {
    global $OUTPUT, $CFG;

    $str = '';

    // Be sure we have something in lastaccess.
    foreach ($courses as $cid => $c) {
        $courses[$cid]->lastaccess = 0 + @$courses[$cid]->lastaccess;
    }

    if (empty($courses)) {
        if (!empty($options['printifempty']) && empty($options['noheading'])) {
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.get_string($title, 'local_my').'</h2>';
            $str .= '</div>';
            $str .= '</div>';
            $str .= $OUTPUT->box(get_string('nocourses','local_my'), 'content');
        }
    } else {
        if (empty($options['noheading'])) {
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.get_string($title, 'local_my').'</h2>';
            $str .= '</div>';
            $str .= '</div>';
            $str .= '<div class="content">';
        }
        $str .= '<table class="courselist" width="100%">';
        if (!empty($options['withoverview'])) {
            $str .= local_print_course_overview($courses);
        } elseif (!empty($options['withcats'])) {
            $str .= local_print_courses_by_cats($courses, $options, true);
        } else {
            foreach ($courses as $course) {
                $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));

                $str .= '<tr valign="top">';
                $str .= '<td class="courserow">';
                $str .= '<a class="courselink" href="'.$courseurl.'">'.format_string($course->fullname).'</a>';
                if (!empty($options['withdescription'])) {
                    $str .= '<p class="coursedescription">'.format_text($course->summary, $course->summaryformat).'</p>';
                }
                $str .= '</td>';
                $str .= '</tr>';
            }
        }
        $str .= '</table>';

        if (empty($options['noheading'])) {
            $str .= '</div>';
        }
    }

    if ($return) {
        return $str;
    }
    echo $str;
}

/**
 * an adaptation of the standard print_course_overview()
 * @param array $courses a course array to print
 * @param boolean $return if true returns the string
 * @return the rendered view if return is true
 */
function local_print_course_overview($courses, $return = false) {
    global $CFG, $PAGE, $OUTPUT;

    // Be sure we have something in lastaccess.
    foreach ($courses as $cid => $c) {
        $courses[$cid]->lastaccess = 0 + @$courses[$cid]->lastaccess;
    }

    $overviews = array();
    if ($modules = get_plugin_list_with_function('mod', 'print_overview')) {
        foreach ($modules as $fname) {
            $fname($courses,$overviews);
        }
    }

    $renderer = $PAGE->get_renderer('block_course_overview');

    $str = $renderer->course_overview($courses, $overviews);

    if ($return) {
        return $str;
    }
    echo $str;
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
function local_prefetch_course_areas(&$excludecourses) {
    global $USER, $CFG, $OUTPUT, $DB;

    $allcourses = enrol_get_my_courses('id,shortname');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            unset($allcourses[$id]);
        }
    }

    if (empty($CFG->localmycourseareas)) {
        // Performance quick trap.
        return array();
    }

    $prefetchareacourses = array();

    for ($i = 0; $i < $CFG->localmycourseareas ; $i++) {

        $coursearea = 'localmycoursearea'.$i;
        $mastercategory = $DB->get_record('course_categories', array('id' => $CFG->$coursearea));

        $key = 'localmycoursearea'.$i;
        $categoryid = $CFG->$key;

        // filter courses of this area
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        foreach ($allcourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $prefetchareacourses[$c->id] = $c;
            }
        }
    }

    return $prefetchareacourses;
}

function local_my_hide_home() {
    global $CFG;
    
    if ($CFG->localmyenable) {
        return false;
    }

    if ($CFG->localmyforce) {
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
function local_get_user_capability_course($capability, $userid = null, $doanything = true, $fieldsexceptid = '', $orderby = '') {
    global $DB;

    // Convert fields list and ordering
    $fieldlist = '';
    if ($fieldsexceptid) {
        $fields = explode(',', $fieldsexceptid);
        foreach($fields as $field) {
            $fieldlist .= ',c.'.$field;
        }
    }
    if ($orderby) {
        $fields = explode(',', $orderby);
        $orderby = '';
        foreach($fields as $field) {
            if ($orderby) {
                $orderby .= ',';
            }
            $orderby .= 'c.'.$field;
        }
        $orderby = 'ORDER BY '.$orderby;
    }

    // Obtain a list of everything relevant about all courses including context but
    // only where user has roles directly inside.
    // Note the result can be used directly as a context (we are going to), the course
    // fields are just appended.

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
        GROUP BY
            c.id

        $orderby";

    $rs = $DB->get_recordset_sql($sql, array($userid));

    // Check capability for each course in turn
    foreach ($rs as $course) {
        context_helper::preload_from_record($course);
        $context = context_course::instance($course->id);
        if (has_capability($capability, $context, $userid, $doanything)) {
            // We've got the capability. Make the record look like a course record
            // and store it
            $courses[] = $course;
        }
    }
    $rs->close();
    return empty($courses) ? false : $courses;
}
