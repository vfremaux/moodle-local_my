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
 *
 * This file contains content output modules for the my page.
 * All printable modules are function whith names starting with local_my_print_<modulename>()
 */
if (!defined('MOODLE_EARLY_INTERNAL')) {
    defined('MOODLE_INTERNAL') || die();
}

require_once($CFG->dirroot.'/local/my/extlibs/Mobile_Detect.php');

/**
 * this fixes the limit where courses can display overview.
 */
define('MAX_COURSE_OVERVIEWED_LIST', 20);

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_my_courses(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER;

    $debug = 0;

    $config = get_config('local_my');

    $mycourses = enrol_get_my_courses('id, shortname, fullname');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            unset($mycourses[$id]);
        }
    }

    foreach ($mycourses as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    echo "reject meta $id as meta disabled";
                }
                unset($mycourses[$id]);
                continue;
            }
        }
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $str = '';

    $str .= '<div class="block block_my_courses">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('mycourses', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';
    $str .= '<table id="mycourselist" width="100%" class="courselist">';

    if (empty($mycourses)) {
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= get_string('nocourses', 'local_my');
        $str .= '</td>';
        $str .= '</tr>';
    } else {
        $str .= '<tr valign="top"><td>';
        if (count($mycourses) < (0 + @$config->maxoverviewedlistsize)) {
            $str .= local_print_course_overview($mycourses, array('gaugewidth' => 150, 'gaugeheight' => 20));
        } else {
            if (count($mycourses) < (0 + @$config->maxuncategorizedlistsize)) {
                // Solve a performance issue for people having wide access to courses.
                $options = array('noheading' => true, 'withcats' => false, 'gaugewidth' => 150, 'gaugeheight' => 20);
            } else {
                $options = array('noheading' => true, 'withcats' => true, 'gaugewidth' => 150, 'gaugeheight' => 20);
            }
            $str .= local_my_print_courses('mycourses', $mycourses, $options);
        }
        $str .= '</td></tr>';

        if ($debug) {
            foreach ($myauthcourses as $ac) {
                echo "exclude authored $ac->id as mine <br/>";
            }
        }

        $excludedcourses = $excludedcourses + $mycourses;
    }

    $str .= '</table>';
    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_my_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE;

    $renderer = $PAGE->get_renderer('local_my');

    $config = get_config('local_my');

    $mycourses = enrol_get_my_courses('id, shortname, fullname');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            unset($mycourses[$id]);
        }
    }

    $debug = optional_param('debug', false, PARAM_BOOL);

    foreach ($mycourses as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    echo "reject meta $id as meta disabled";
                }
                unset($mycourses[$id]);
                continue;
            }
        }
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $str = '';

    $str .= '<div class="block block_my_courses">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('mycourses', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';

    if (empty($mycourses)) {
        $str .= '<table id="mycourselist" width="100%" class="courselist">';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= get_string('nocourses', 'local_my');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
    } else {
        $str .= $renderer->courses_slider(array_keys($mycourses));
        $excludedcourses = $excludedcourses + $mycourses;
    }

    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

function local_my_print_recent_courses() {
    global $DB, $USER, $PAGE;

    $logstoreinfo = local_my_get_logstore_info();
    $renderer = $PAGE->get_renderer('local_my');

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

    $recentcourses = $DB->get_records_sql($sql, array($USER->id));

    if (!empty($recentcourses)) {
        $str = '';

        $str .= '<div class="block block_recent_courses">';

        $str .= '<div class="header">';
        $str .= '<div class="title">';
        $str .= '<h2>'.get_string('recentcourses', 'local_my').'</h2>';
        $str .= '</div>';
        $str .= '</div>';

        $str .= '<div class="content constainer-fluid">';
        $str .= '<div id="mycourselist" width="100%" class="courselist row-fluid clearfix">';
        foreach ($recentcourses as $c) {
            $str .= $renderer->course_as_box($c);
        }
        $str .= '</div>';
        $str .= '</div>';

        $str .= '</div>';

        return $str;
    }
}

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_authored_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $CFG, $DB;

    $debug = 0;

    $myauthcourses = local_get_my_authoring_courses();

    // Post 2.5.
    include_once($CFG->dirroot.'/lib/coursecatlib.php');
    $mycatlist = coursecat::make_categories_list('moodle/course:create');

    if (!empty($excludedcourses)) {
        foreach (array_keys($excludedcourses) as $cid) {
            if ($debug) {
                echo "rejected authored $cid as excluded</br/>";
            }
            unset($myauthcourses[$cid]);
        }
    }

    $str = '';

    $hascontent = false;
    if (!empty($mycatlist) || !empty($myauthcourses)) {
        $str .= '<div class="block block_my_authored_courses">';
        $str .= '<div class="header">';
        $str .= '<div class="title">';
        $str .= '<h2>'.get_string('myauthoringcourses', 'local_my').'</h2>';
        $str .= '</div>';
        $str .= '</div>';
        $str .= '<div class="content">';
        $hascontent = true;
    }

    if (!empty($mycatlist)) {

        $levels = CONTEXT_COURSE.','.CONTEXT_COURSECAT;
        $cancreate = local_my_has_capability_somewhere('moodle/course:create', false, false, true, $levels);

        $catids = array_keys($mycatlist);
        $firstcatid = array_shift($catids);
        $button0 = '';
        $button1 = '';
        $button2 = '';
        $button3 = '';

        if ($cancreate) {
            $params = array('view' => 'courses', 'categoryid' => $firstcatid);
            $label = get_string('managemycourses', 'local_my');
            $button0 = $OUTPUT->single_button(new moodle_url('/course/management.php', $params), $label);

            $label = get_string('newcourse', 'local_my');
            $button1 = $OUTPUT->single_button(new moodle_url('/local/my/create_course.php'), $label);

            if (is_dir($CFG->dirroot.'/local/coursetemplates')) {
                $config = get_config('local_coursetemplates');
                if ($config->enabled && $config->templatecategory) {
                    $params = array('category' => $config->templatecategory, 'visible' => 1);
                    if ($DB->count_records('course', $params)) {
                        $buttonurl = new moodle_url('/local/coursetemplates/index.php');
                        $button2 = $OUTPUT->single_button($buttonurl, get_string('newcoursefromtemplate', 'local_my'));
                    }
                }
            }

            // Need fetch a context where user has effective capability.

            $powercontext = local_get_one_of_my_power_contexts();
            if ($powercontext) {
                $params = array('contextid' => $powercontext->id);
                $buttonurl = new moodle_url('/backup/restorefile.php', $params);
                $button3 = $OUTPUT->single_button($buttonurl, get_string('restorecourse', 'local_my'));
            }
        }
        $str .= '<div class="right-button course-creation-buttons">'.$button0.' '.$button1.' '.$button2.' '.$button3.'</div>';
    }

    if (!empty($myauthcourses)) {
        $str .= '<table id="myauthoredcourselist" width="100%" class="generaltable courselist">';
        $str .= '<tr valign="top"><td>';
        if (count($myauthcourses) < 0 + @$config->maxoverviewedlistsize) {
            $str .= local_print_course_overview($myauthcourses, true, array('gaugewidth' => 0, 'gaugeheight' => 0));
        } else {
            if (count($myauthcourses) < (0 + @$config->maxuncategorizedlistsize)) {
                // Solve a performance issue for people having wide access to courses.
                $options = array('noheading' => true,
                                 'withcats' => false,
                                 'nocompletion' => true,
                                 'gaugewidth' => 0,
                                 'gaugeheight' => 0);
            } else {
                // Solve a performance issue for people having wide access to courses.
                $options = array('noheading' => true,
                                 'withcats' => true,
                                 'nocompletion' => true,
                                 'gaugewidth' => 0,
                                 'gaugeheight' => 0);
            }
            $str .= local_my_print_courses('myauthcourses', $myauthcourses, $options, true);
        }
        $str .= '</td></tr>';
        $str .= '</table>';

        if ($debug) {
            foreach ($myauthcourses as $ac) {
                echo "exclude authored $ac->id as authored <br/>";
            }
        }
        $excludedcourses = $excludedcourses + $myauthcourses;
    }

    if ($hascontent) {
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_my_templates(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $CFG, $DB, $USER;

    if (!is_dir($CFG->dirroot.'/local/coursetemplates')) {
        return '';
    }

    $config = get_config('local_coursetemplates');

    if (!$config->templatecategory) {
        return '';
    }

    // Checks if category was deleted. Case IGS 14/09/2016.
    if (!$DB->record_exists('course_categories', array('id' => $config->templatecategory))) {
        return '';
    }

    $mytemplates = local_get_my_templates();

    $templatecatcontext = context_coursecat::instance($config->templatecategory);

    $canview = false;
    if ($mytemplates) {
        foreach ($mytemplates as $tid => $t) {
            $coursecontext = context_course::instance($t->id);
            if (has_capability('moodle/course:manageactivities', $coursecontext)) {
                $canview = true;
            } else if (!has_capability('moodle/course:view', $coursecontext)) {
                unset($mytemplates[$tid]);
            }
        }
    }

    if (has_capability('moodle/course:create', $templatecatcontext, $USER->id, true)) {
        $canview = true;
    }

    if (!$canview) {
        return '';
    }

    // Post 2.5.
    if (!empty($excludedcourses)) {
        foreach (array_keys($excludedcourses) as $cid) {
            unset($mytemplates[$cid]);
        }
    }

    $str = '';

    $str .= '<div class="block block_my_templates">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('mytemplates', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';

    $button1 = '';
    if (is_dir($CFG->dirroot.'/local/coursetemplates')) {
        $config = get_config('local_coursetemplates');
        if ($config->enabled && $config->templatecategory) {
            if ($DB->count_records('course', array('category' => $config->templatecategory, 'visible' => 1))) {
                $params = array('category' => $config->templatecategory, 'forceediting' => true);
                $buttonurl = new moodle_url('/local/coursetemplates/index.php', $params);
                $button1 = $OUTPUT->single_button($buttonurl, get_string('newtemplate', 'local_my'));
            } else {
                $button1 = get_string('templateinitialisationadvice', 'local_my');
            }
        }
    }
    $str .= '<div class="right-button">'.$button1.'</div>';

    if (!empty($mytemplates)) {
        $str .= local_my_print_courses('mytemplates', $mytemplates, array('noheading' => 1, 'nocompletion' => 1));

        $excludedcourses = $excludedcourses + $mytemplates;
    }

    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints the specific courses area as a 3 column link list
 */
function local_my_print_course_areas(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $DB;

    $allcourses = enrol_get_my_courses('id, shortname, fullname');
    $config = get_config('local_my');

    $options = array();
    $options['withcats'] = 0;

    foreach ($allcourses as $id => $c) {
        $allcourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    if (empty($config->courseareas)) {
        // Performance quick trap.
        return;
    }

    $str = '';

    $str .= '<table id="mycourseareas" width="100%">';
    $str .= '<tr valign="top">';

    $reali = 1;
    for ($i = 0; $i < $config->courseareas; $i++) {

        $coursearea = 'coursearea'.$i;
        if (empty($config->$coursearea)) {
            continue;
        }

        $mastercategory = $DB->get_record('course_categories', array('id' => $config->$coursearea));
        if (!$mastercategory) {
            continue;
        }

        $key = 'coursearea'.$i;
        $categoryid = $config->$key;

        // Filter courses of this area.
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        $areacourses = array();
        foreach ($allcourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $areacourses[$c->id] = $c;
                $excludedcourses[$c->id] = 1;
            }
        }

        $colwidth = false;
        if ($config->courseareas % 3 == 0) {
            $colwidth = 33;
        }

        if (!$colwidth) {
            if ($config->courseareas % 2 == 0) {
                $colwidth = 50;
            }
        }

        if (!$colwidth) {
            switch ($config->courseareas) {
                case 1:
                    $colwidth = 100;
                    break;
                case 2:
                    $colwidth = 50;
                    break;
                default:
                    $colwidth = 33;
            }
        }

        if (!empty($areacourses)) {
            if ($reali % 3 == 0) {
                $str .= '</tr></tr valign="top">';
            }
            $str .= '<td width="'.$colwidth.'%">';

            $str .= $OUTPUT->heading(format_string($mastercategory->name), 2, 'headingblock header');
            $str .= '<div class="block">';
            $str .= '<table id="courselistarea'.$reali.'" width="100%" class="courselist generaltable">';
            $str .= '<tr valign="top"><td>';
            if (count($areacourses) < $config->maxoverviewedlistsize) {
                $str .= local_print_course_overview($areacourses, array('gaugewidth' => 80, 'gaugeheight' => 15));
            } else {
                // Solve a performance issue for people having wide access to courses.
                $str .= local_print_courses_by_cats($areacourses, $options);
            }
            $str .= '</td></tr>';
            $str .= '</table>';
            $str .= '</div>';

            $str .= '</td>';

            $reali++;
        }
    }

    $str .= '</tr>';
    $str .= '</table>';

    return $str;
}

/**
 * Prints the specific courses area as a 3 column link list
 */
function local_my_print_course_areas_and_availables(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $DB;

    $debug = 0;

    $mycourses = enrol_get_my_courses('id,shortname,fullname');
    $availablecourses = local_get_enrollable_courses();

    $config = get_config('local_my');
    if (!$config->courseareas) {
        // Performance quick trap.
        return;
    }

    if (empty($mycourses)) {
        $mycourses = array(); // Be sure of that !
    }
    if (empty($availablecourses)) {
        $availablecourses = array();
    }

    if (empty($courseareacourses)) {
        $courseareacourses = array();
    }

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if (in_array($id, array_keys($courseareacourses))) {
                continue;
            }
            if ($debug) {
                echo "reject enrolled as excluded $id <br/>";
            }
            if (array_key_exists($id, $mycourses)) {
                unset($mycourses[$id]);
            }
            if (array_key_exists($id, $availablecourses)) {
                if ($debug) {
                    echo "reject available as excluded $id <br/>";
                }
                unset($availablecourses[$id]);
            }
        }
    }

    foreach ($mycourses as $id => $c) {
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $str = '';

    $str .= '<table id="mycourseareas" width="100%">';
    $str .= '<tr valign="top">';

    $options['noheading'] = 1;
    $options['nooverview'] = 1;
    $options['withdescription'] = 0;
    $options['withcats'] = $config->printcategories;
    $options['withcats'] = 0; // Which one ???
    $options['gaugewidth'] = 60;
    $options['gaugeheight'] = 15;

    $reali = 1;
    for ($i = 0; $i < $config->courseareas; $i++) {

        $coursearea = 'coursearea'.$i;
        $mastercategory = $DB->get_record('course_categories', array('id' => $config->$coursearea));

        $key = 'coursearea'.$i;
        $categoryid = $config->$key;

        if ($debug) {
            echo " > coursearea $i <br/>";
        }
        // Filter courses of this area.
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        $myareacourses = array();
        foreach ($mycourses as $c) {
            if ($debug) {
                echo " checking enrolled $c->id ... ";
            }
            if (in_array($c->category, $retainedcategories)) {
                if ($debug) {
                    echo " accept enrolled $c->id <br/>";
                }
                $myareacourses[$c->id] = $c;
                $excludedcourses[$c->id] = 1;
            } else {
                if ($debug) {
                    echo " reject enrolled $c->id not in cat <br/>";
                }
            }
        }

        $availableareacourses = array();
        foreach ($availablecourses as $c) {
            if ($debug) {
                echo " checking available $c->id ... ";
            }
            if (!isset($c->summary)) {
                $c->summary = $DB->get_field('course', 'summary', array('id' => $id));
            }
            if (in_array($c->category, $retainedcategories)) {
                if ($debug) {
                    echo " accept available $c->id ... ";
                }
                $availableareacourses[$c->id] = $c;
                $excludedcourses[$c->id] = 1;
            } else {
                if ($debug) {
                    echo " reject enrollable $c->id not in cat <br/>";
                }
            }
        }

        if (!empty($myareacourses) || !empty($availableareacourses)) {
            if ($reali % 3 == 0) {
                $str .= '</tr><tr valign="top">';
            }
            $str .= '<td width="33%">';

            $str .= '<div class="block block_coursearea_courses">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= $OUTPUT->heading(format_string($mastercategory->name), 2, 'headingblock header');
            $str .= '</div>';
            $str .= '</div>';
            $str .= '<div class="content">';
            $str .= '<table id="courselistarea'.$reali.'" width="100%" class="courselist">';
            $str .= '<tr valign="top">';
            $str .= '<td>';
            if (empty($options['nooverview'])) {
                if (count($myareacourses) < $config->maxoverviewedlistsize) {
                    $str .= local_print_course_overview($myareacourses, $options);
                } else {
                    // Solve a performance issue for people having wide access to courses.
                    $str .= local_print_courses_by_cats($myareacourses, $options);
                }
            } else {
                // Aggregate my courses with the available and print in one unique list.
                $availableareacourses = $myareacourses + $availableareacourses;
            }
            if (!empty($availableareacourses)) {
                $str .= local_my_print_courses('available', $availableareacourses, $options);
            }
            $str .= '</td>';
            $str .= '</tr>';
            $str .= '</table>';
            $str .= '</div>';
            $str .= '</div>';

            $str .= '</td>';

            $reali++;
        }
    }

    $str .= '</tr>';
    $str .= '</table>';

    return $str;
}

/**
 * Prints the available (enrollable) courses as simple link entries
 */
function local_my_print_available_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT;

    $str = '';

    $config = get_config('local_my');

    $courses = local_get_enrollable_courses();
    if (empty($courses)) {
        return;
    }

    $overcount = 0;
    if (!empty($config->maxavailablelistsize)) {
        $overcount = (count($courses) > $config->maxavailablelistsize);
        if ($overcount) {
            $courses = array_slice($courses, 0, 11);
        }
    }

    if (!empty($excludedcourses)) {
        $excludedids = array_keys($excludedcourses);
    } else {
        $excludedids = array();
    }

    foreach ($courses as $cid => $foo) {
        if (in_array($cid, $excludedids)) {
            unset($courses[$cid]);
        }
    }

    if (!count($courses)) {
        // No more courses to show once filtered.
        return '';
    }

    $options['printifempty'] = 0;
    $options['withcats'] = 2;
    $options['nocompletion'] = 1;

    $str .= $OUTPUT->box_start('block block_my_available_courses');
    $str .= local_my_print_courses('availablecourses', $courses, $options);
    if ($overcount) {
        $allcoursesurl = new moodle_url('/local/my/enrollable_courses.php');
        $link = '<a href="'.$allcoursesurl.'">'.get_string('seealllist', 'local_my').'</a>';
        $str .= $OUTPUT->box($link, 'local-my-overcount');
    }
    $str .= $OUTPUT->box_end();

    return $str;
}

/**
 * Prints the news forum as a list of full deployed discussions.
 */
function local_my_print_latestnews_full() {
    global $SITE, $CFG, $SESSION, $USER;

    $str = '';
    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // Fetch news forum context for proper filtering to happen.
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
        $attrs = array('href' => '#skipsitenews', 'class' => 'skip-block');
        echo html_writer::tag('a', get_string('skipa', 'access', core_text::strtolower(strip_tags($forumname))), $attrs);

        if (isloggedin()) {
            $SESSION->fromdiscussion = $CFG->wwwroot;
            $subtext = '';
            if (forum_is_subscribed($USER->id, $newsforum)) {
                if (!forum_is_forcesubscribed($newsforum)) {
                    $subtext = get_string('unsubscribe', 'forum');
                }
            } else {
                $subtext = get_string('subscribe', 'forum');
            }
            $str .= '<div class="block block_my_news">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.$forumname.'</h2>';
            $str .= '</div>';
            $str .= '</div>';
            $str .= '<div class="content">';
            $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
            $str .= html_writer::tag('div', html_writer::link($suburl, $subtext), array('class' => 'subscribelink'));
            $str .= '</div>';
        } else {
            $str .= '<div class="block block_my_news">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.$forumname.'</h2>';
            $str .= '</div>';
            $str .= '</div>';
            $str .= '<div class="content">';
            $str .= '</div>';
        }

        ob_start();
        forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'plain', 'p.modified DESC');
        $str .= ob_get_clean();
        $str .= '</div>';
        $str .= html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
    }

    return $str;
}

/**
 * Prints the news forum as simple compact list of discussion headers.
 */
function local_my_print_latestnews_headers() {
    global $PAGE, $SITE, $CFG, $OUTPUT, $USER, $SESSION;

    $str = '';

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        $renderer = $PAGE->get_renderer('local_my');
        $str .= $renderer->print_forum_link($newsforum, $forumname);

        if (isloggedin()) {
            if (!isset($SESSION)) {
                $SESSION = new StdClass();
            }
            $SESSION->fromdiscussion = $CFG->wwwroot;
            $subtext = '';
            if (forum_is_subscribed($USER->id, $newsforum)) {
                if (!forum_is_forcesubscribed($newsforum)) {
                    $subtext = get_string('unsubscribe', 'forum');
                }
            } else {
                $subtext = get_string('subscribe', 'forum');
            }
            $str .= '<div class="block block_my_newsheads">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.$forumname.'</h2>';
            $str .= '</div>';
            $str .= '</div>';
            $str .= '<div class="content">';
            $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
            $str .= html_writer::tag('div', html_writer::link($suburl, $subtext), array('class' => 'subscribelink'));
            $str .= '</div>';
        } else {
            $str .= '<div class="block block_my_newsheads">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= $OUTPUT->heading($forumname, 2, 'headingblock header');
            $str .= '</div>';
            $str .= '</div>';
        }

        ob_start();
        forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'header', 'p.modified DESC');
        $str .= ob_get_clean();
        $str .= '</div>';
        $str .= html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
    }

    return $str;
}

/**
 * Same as "full", but removes all subscription or any discussion commandes.
 */
function local_my_print_latestnews_simple() {
    global $PAGE, $SITE, $CFG, $OUTPUT, $DB, $SESSION;

    $str = '';

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot .'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        $renderer = $PAGE->get_renderer('local_my');
        $str .= $renderer->print_forum_link($newsforum, $forumname);

        if (isloggedin()) {
            $SESSION->fromdiscussion = $CFG->wwwroot;
            $subtext = '';
            $str .= '<div class="block">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.$forumname.'</h2>';
            $str .= '</div></div>';
        } else {
            $str .= '<div class="block">';
            $str .= '<div class="header">';
            $str .= '<div class="title">';
            $str .= '<h2>'.$forumname.'</h2>';
            $str .= '</div></div>';
        }

        $str .= '<div class="content">';
        $str .= '<table width="100%" class="newstable">';
        $newsdiscussions = $DB->get_records('forum_discussions', array('forum' => $newsforum->id), 'timemodified DESC');
        foreach ($newsdiscussions as $news) {
            $str .= '<tr valign="top">';
            $str .= '<td width="80%">';
            $forumurl = new moodle_url('/mod/forum/discuss.php', array('d' => $news->id));
            $str .= '<a href="'.$forumurl.'">'.$news->name.'</a>';
            $str .= '</td>';
            $str .= '<td align="right" width="20%">';
            $str .= '('.userdate($news->timemodified).')';
            $str .= '</td>';
            $str .= '</tr>';
        }
        $str .= '</table>';
        $str .= '</div>';
        $str .= '</div>';
        $str .= html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
    }

    return $str;
}

/**
 * Prints a static div with content stored into central configuration.
 * If index points to a recognizable profile field, will check the current user
 * profile field to display.
 */
function local_my_print_static($index) {
    global $CFG, $DB, $USER, $OUTPUT;

    $context = context_system::instance();
    $str = '';

    if (!file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
        return $OUTPUT->notification(get_string('staticguitextsnotinstalled', 'local_my'));
    }

    include_once($CFG->dirroot.'/local/staticguitexts/lib.php');

    if (preg_match('/profile_field_(.*?)_(.*)/', $index, $matches)) {

        // Provide content for an only modality of a profile selector.

        $profileexpectedvalue = core_text::strtolower($matches[2]);
        if (is_numeric($matches[1])) {
            $fieldid = $matches[1];
            $field = $DB->get_record('user_info_field', array('id' => $fieldid));
        } else {
            $fieldname = $matches[1];
            $field = $DB->get_record('user_info_field', array('shortname' => $fieldname));
            $fieldid = $field->id;
        }

        $params = array('userid' => $USER->id, 'fieldid' => $fieldid);
        $profilevalue = core_text::strtolower($DB->get_field('user_info_data', 'data', $params));

        if ($field->datatype == 'menu') {
            $modalities = explode("\n", $field->param1);
        }

        $class = '';
        if (($profilevalue != $profileexpectedvalue)) {
            if (!has_capability('moodle/site:config', $context)) {
                return '';
            } else {
                $class = 'adminview';
            }
        }

        // Normal user, one sees his own.
        $str .= '<div id="custommystaticarea_'.$index.'" class="local-my-statictext '.$class.'">';
        if ($class == 'adminview') {
            $e = new StdClass;
            $e->field = $field->name;
            $e->value = $profileexpectedvalue;
            $str .= get_string('adminview', 'local_my', $e).'<br/>';
        }
        $str .= local_print_static_text('custommystaticarea_'.$index, $CFG->wwwroot.'/my/index.php', '', true);
        $str .= '</div>';

    } else if (preg_match('/profile_field_(.*)$/', $index, $matches)) {

        // Provide values for all modalities of a profile selector.

        if (is_numeric($matches[1])) {
            $fieldid = $matches[1];
            $field = $DB->get_record('user_info_field', array('id' => $fieldid));
        } else {
            $fieldname = $matches[1];
            $field = $DB->get_record('user_info_field', array('shortname' => $fieldname));
            if ($field) {
                $fieldid = $field->id;
            } else {
                $str = $OUTPUT->notification(get_string('fieldnotfound', 'local_my', $fieldname));
            }
        }

        if (!$field) {
            return;
        }

        if ($field->datatype == 'menu') {
            $modalities = explode("\n", $field->param1);
        }

        $params = array('userid' => $USER->id, 'fieldid' => $fieldid);
        $profilevalue = core_text::strtolower($DB->get_field('user_info_data', 'data', $params));
        $profilevalue = trim($profilevalue);
        $profilevalue = str_replace(' ', '_', $profilevalue);

        // This is a global match catching all values.
        if (has_capability('moodle/site:config', $context)) {

            // I'm administrator, so i can see all modalities and edit them.
            if (!isset($modalities)) {
                $sql = "
                    SELECT
                        DISTINCT(data) as data
                    FROM
                        {user_info_data}
                    WHERE
                        fieldid = ?
                ";

                $modalities = $DB->get_records_sql($sql, array($fieldid));
            }

            if ($modalities) {

                $modstrs = array();
                $modoptions = array();

                foreach ($modalities as $modality) {

                    // Reformat key for token integrity.
                    if (is_object($modality)) {
                        $modality = core_text::strtolower($modality->data);
                    } else {
                        $modality = core_text::strtolower($modality);
                    }
                    $modality = trim($modality);
                    $modality = str_replace(' ', '_', $modality);
                    $modalindex = $index.'_'.$modality;

                    $tmp = '<div id="custommystaticarea_'.$modalindex.'" class="editing local-my-statictext">';
                    $tmp .= '<div class="staticareaname">';
                    $a = new StdClass;
                    $a->profile = $field->shortname;
                    $a->data = $modality;
                    $tmp .= get_string('contentfor', 'local_my', $a);
                    $tmp .= '</div>';
                    $tmp .= '<div class="content" id="">';
                    $tmp .= local_print_static_text('custommystaticarea_'.$modalindex, $CFG->wwwroot.'/my/index.php', '', true);
                    $tmp .= '</div>';
                    $tmp .= '</div>';
                    $modstrs[] = $tmp;

                    $modoptions[$modality] = $modality;
                }

                /*
                $str .= '<div id="custommystaticarea_ctl_'.$index.'" class="editing local-my-statictext-ctl">';
                $str .= html_writer::select($modoptions, 'modalities');
                $str .= '</div>';
                */

                $str .= implode("\n", $modstrs);
            }
            return $str;
        } else {
            // Normal user, one sees his own.

            $modalindex = $index.'_'.$profilevalue;
            $str .= '<div id="custommystaticarea_'.$modalindex.'" class="local-my-statictext">';
            $str .= local_print_static_text('custommystaticarea_'.$modalindex, $CFG->wwwroot.'/my/index.php', '', true);
            $str .= '</div>';
        }

        $params = array('userid' => $USER->id, 'fieldid' => $fieldid);
        $profilevalue = core_text::strtolower($DB->get_field('user_info_data', 'data', $params));

    }

    return $str;
}

/**
 * Prints a widget with information about me.
 */
function local_my_print_me() {
    global $OUTPUT, $USER, $CFG;

    $context = context_system::instance();
    $str = '';

    $identityfields = array_flip(explode(',', $CFG->showuseridentity));

    if (has_capability('moodle/user:viewhiddendetails', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    $str .= '<div class="local-my-userprofilebox clearfix">';
    $str .= '<div class="profilepicture" style="float:left;margin-right:20px">';
    $str .= $OUTPUT->user_picture($USER, array('size' => 50));
    $str .= '</div>';
    $str .= '<div class="username">';
    $str .= $OUTPUT->heading(fullname($USER));
    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints a widget with more information about me.
 */
function local_my_print_fullme() {
    global $OUTPUT, $USER, $CFG;

    $context = context_system::instance();
    $str = '';

    $identityfields = array_flip(explode(',', $CFG->showuseridentity));

    if (has_capability('moodle/user:viewhiddendetails', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    $str .= '<div class="userprofilebox clearfix">';
    $str .= '<div class="profilepicture" style="float:left;margin-right:20px">';
    $str .= $OUTPUT->user_picture($USER, array('size' => 50));
    $str .= '</div>';
    $str .= '<div class="username">';
    $str .= $OUTPUT->heading(fullname($USER));

    $str .= '<table id="my-me" class="list" width="70%">';

    $str .= local_my_print_row(get_string("username").":", "$USER->username");

    if (!isset($hiddenfields['firstaccess'])) {
        if ($USER->firstaccess) {
            $datestring = userdate($USER->firstaccess)."&nbsp; (".format_time(time() - $USER->firstaccess).")";
        } else {
            $datestring = get_string("never");
        }
        $str .= local_my_print_row(get_string("firstaccess").":", $datestring);
    }
    if (!isset($hiddenfields['lastaccess'])) {
        if ($USER->lastaccess) {
            $datestring = userdate($USER->lastaccess)."&nbsp; (".format_time(time() - $USER->lastaccess).")";
        } else {
            $datestring = get_string("never");
        }
        $str .= local_my_print_row(get_string("lastaccess").":", $datestring);
    }

    if (isset($identityfields['institution']) && $USER->institution) {
        $str .= local_my_print_row(get_string("institution").":", "$USER->institution");
    }

    if (isset($identityfields['department']) && $USER->department) {
        $str .= local_my_print_row(get_string("department").":", "$USER->department");
    }

    if (isset($identityfields['country']) && !isset($hiddenfields['country']) && $USER->country) {
        $str .= local_my_print_row(get_string('country') . ':', get_string($USER->country, 'countries'));
    }

    if (isset($identityfields['city']) && !isset($hiddenfields['city']) && $USER->city) {
        $str .= local_my_print_row(get_string('city') . ':', $USER->city);
    }

    if (isset($identityfields['idnumber']) && $USER->idnumber) {
        $str .= local_my_print_row(get_string("idnumber").":", "$USER->idnumber");
    }

    $str .= '</table>';
    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * This module picks a block instance in the current context and prints its content.
 * The original block may be hidden on the page to avoid info duplicate.
 * @param int $blockid the block id.
 * @param int $contextid the parent context.
 */
function local_my_print_block($blockid, $contextid) {
    global $DB;

    if (!$blockrec = $DB->get_record('block_instances', array('id' => $blockid, 'parentcontextid' => $contextid))) {
        $str = '<div class="block">';
        $str .= $OUTPUT->notification(get_string('errorbadblock', 'local_my'));
        $str .= '</div>';
        return $str;
    }
    $blockinstance = block_instance($blockrec->blockname, $blockrec);

    $content = $blockinstance->get_content()->text;
    $str = '<div class="block">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.$blockinstance->get_title().'</h2>';
    $str .= '</div></div>';

    $str .= '<div class="content">';
    $str .= $content;
    $str .= '</div>';
    $str .= '</div>';
    return $str;
}

/**
 * A utility function
 */
function local_my_print_row($left, $right) {
    $str = "\n".'<tr valign="top">';
    $str .= '<th class="my-label c0" width="40%">';
    $str .= $left;
    $str .= '</th>';
    $str .= '<td class="info c1" width="60%">';
    $str .= $right;
    $str .= '</td>';
    $str .= '</tr>'."\n";
    return $str;
}

/**
 * Prints a github like heat activity map on passed six months
 * @param int $userid the concerned userid
 */
function local_my_print_my_heatmap($userid = 0) {
    global $CFG, $USER;

    $config = get_config('local_my');

    if (!$userid) {
        $userid = $USER->id;
    }

    if (empty($config->heatmaprange)) {
        $config->heatmaprange = 6;
    }

    $localmyheatmaprange = $config->heatmaprange;
    $mb = new Mobile_Detect();
    if ($mb->isMobile()) {
        $localheatmaprange = 3;
    }

    $startdate = time() - (DAYSECS * 30 * ($localmyheatmaprange - 1));
    $startmilli = $startdate * 1000;

    $legendformat = new StdClass();
    // Less than {min} {name}    Formatting of the smallest (leftmost) value of the legend.
    $legendformat->lower = get_string('lower', 'local_my');
    // Between {down} and {up} {name}    Formatting of all the value but the first and the last.
    $legendformat->inner = get_string('inner', 'local_my');
    // More than {max} {name}.
    $legendformat->upper = get_string('upper', 'local_my');
    $jsonlegendformat = json_encode($legendformat);

    $subdomainformat = new StdClass();
    $subdomainformat->empty = '{date}';
    $subdomainformat->filled = get_string('filled', 'local_my');
    $jsonsubdomainformat = json_encode($subdomainformat);

    function i18n_months(&$a, $key) {
        $a = get_string($a, 'local_my');
    }

    $monthnames = array('january', 'february', 'march', 'april', 'may', 'june', 'july',
                        'august', 'september', 'october', 'november', 'december');
    array_walk($monthnames, 'i18n_months');

    $itemname = get_string('frequentationitem', 'local_my');

    $str = '';
    $str .= '<div class="my-modules heatmap">';
    $str .= '<div class="block block_my_heatmap">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2 class="headingblock header">'.get_string('myactivity', 'local_my').'</h2>';
    $str .= '</div></div>';
    $str .= '<div class="content">';
    $str .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/local/my/js/d3/d3.v3.min.js"></script>';
    $str .= '<link rel="stylesheet" href="'.$CFG->wwwroot.'/local/my/js/d3/heatmap/cal-heatmap.css" />';
    $str .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/local/my/js/d3/heatmap/cal-heatmap.min.js"></script>';

    // Little trick to get margin top effective against js changes.
    $str .= '<div id="cal-heatmap" style=";margin-top:10px;"></div>';

    $str .= '<script type="text/javascript">
        var monthnames = '.json_encode($monthnames).';
        var cal = new CalHeatMap();
        var startdate = new Date('.$startmilli.');
        cal.init({
            domain:"month",
            subdomain:"day",
            start:startdate,
            data:"'.$CFG->wwwroot.'/local/my/heatlogs.php?id='.$USER->id.'",
            legendTitleFormat:'.$jsonlegendformat.',
            subDomainTitleFormat:'.$jsonsubdomainformat.',
            itemName:"'.$itemname.'",
            subDomainDateFormat:
            function(date) {
                return date.toLocaleDateString();
            },
            domainLabelFormat: function(date) {
                return monthnames[date.getMonth()];
            },
            range:'.$localmyheatmaprange.'

        });
    </script>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints a module that is the content of the user_mnet_hosts block.
 */
function local_my_print_my_network() {

    $blockinstance = block_instance('user_mnet_hosts');
    $content = $blockinstance->get_content();

    $str = '';

    if (!empty($content->items) || !empty($content->footer)) {

        $str .= '<div class="my-modules network">';
        $str .= '<div class="box block">';
        $str .= '<div class="header">';
        $str .= '<div class="title">';
        $str .= '<h2 class="headingblock">'.get_string('mynetwork', 'local_my').'</h2>';
        $str .= '</div>';
        $str .= '</div>';
        $str .= '<div class="content">';
        if (!empty($content->items)) {
            $str .= '<table width="100%">';
            foreach ($content->items as $item) {
                $icon = array_shift($content->icons);
                $str .= '<tr><td>'.$icon.'</td><td>'.$item.'</td></tr>';
            }
            $str .= '</table>';
        }
        if (!empty($content->footer)) {
            $str .= '<p>'.$content->footer.'</p>';
        }
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}

/**
 * prints a module that is the content of the calendar block
 */
function local_my_print_my_calendar() {
    global $PAGE;

    $blockinstance = block_instance('calendar_month');
    $blockinstance->page = $PAGE;
    $content = $blockinstance->get_content();

    if (!empty($content->text) || !empty($content->footer)) {
        $str = '';

        $str .= '<div class="my-modules calendar">';
        $str .= '<div class="box block">';
        $str .= '<h2 class="headingblock header">'.get_string('mycalendar', 'local_my').'</h2>';
        $str .= '<div class="content">';
        if (!empty($content->text)) {
            $str .= $content->text;
        }
        if (!empty($content->footer)) {
            $str .= '<p>'.$content->footer.'</p>';
        }
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}

function local_my_print_course_search() {
    global $PAGE;

    $renderer = $PAGE->get_renderer('course');

    $str = '';

    $search = optional_param('search', '', PARAM_TEXT);

    $str .= '<div class="my-modules coursesearch">';
    $str .= '<div class="box block">';
    $str .= '<h2 class="headingblock header">'.get_string('coursesearch', 'local_my').'</h2>';
    $str .= '<div class="content">';
    $str .= $renderer->course_search_form($search, 'plain');
    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

