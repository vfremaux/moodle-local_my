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
 * Prints the "classical" "My Courses" area, course for students that are not displayed elsewhere.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
<<<<<<< HEAD
function local_my_print_my_courses(&$excludedcourses, &$courseareacourses, $slider = 0) {
=======
function local_my_print_my_courses(&$excludedcourses, &$courseareacourses) {
<<<<<<< HEAD
>>>>>>> MOODLE_34_STABLE
    global $DB, $USER, $OUTPUT, $PAGE;
=======
    global $DB, $USER, $OUTPUT, $PAGE, $CFG;
>>>>>>> MOODLE_34_STABLE

    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    $config = get_config('local_my');
    $renderer = $PAGE->get_renderer('local_my');

    $mycourses = enrol_get_my_courses('id, shortname, fullname');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if ($debug) {
                echo "reject $id as excluded \n";
            }
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

    $template = new StdClass;
    $template->mycoursesstr = get_string('mycourses', 'local_my');

    if (empty($mycourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
    } else {
        $template->hascourses = true;
        if (count($mycourses) < (0 + @$config->maxoverviewedlistsize)) {
            $template->overviewedcourses = $renderer->course_overview($mycourses, array('gaugewidth' => 150, 'gaugeheight' => 20));
        } else {
            if (count($mycourses) < (0 + @$config->maxuncategorizedlistsize)) {
                // Solve a performance issue for people having wide access to courses.
                $options = array('noheading' => true, 'withcats' => false, 'gaugewidth' => 150, 'gaugeheight' => 20);
            } else {
                $options = array('noheading' => true, 'withcats' => true, 'gaugewidth' => 150, 'gaugeheight' => 20);
            }
            $template->simplecourses = local_my_print_courses('mycourses', $mycourses, $options);
        }

        if ($debug) {
            $debuginfo = '';
            foreach ($mycourses as $ac) {
                $debuginfo .= "exclude authored $ac->id as mine <br/>";
            }
            $template->debuginfo = $debuginfo;
        }

        $excludedcourses = array_merge($excludedcourses, array_keys($mycourses));
    }

    return $OUTPUT->render_from_template('local_my/my_courses', $template);
}

/**
 * Prints the slider form of "My Courses" area, that is, courses i'm stdying in.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_my_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE, $OUTPUT;

    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    $renderer = $PAGE->get_renderer('local_my');

    $config = get_config('local_my');

    $mycourses = enrol_get_my_courses('id, shortname, fullname');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if ($debug) {
                echo "reject $id as excluded \n";
            }
            unset($mycourses[$id]);
        }
    }

    $debuginfo = '';
    foreach ($mycourses as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    $debuginfo .= "reject meta $id as meta disabled<br/>";
                }
                unset($mycourses[$id]);
                continue;
            }
        }
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $template = new StdClass;
    $template->widgetname = 'my_courses_slider';

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    $template->courselisttitlestr = get_string('mycourses', 'local_my');
    $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));

    if (empty($mycourses)) {
        $template->hascourses = false;
    } else {
        $template->hascourses = true;
        $template->courseslider = $renderer->courses_slider(array_keys($mycourses));
        $excludedcourses = array_merge($excludedcourses, array_keys($mycourses));
<<<<<<< HEAD
    }

    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints the slider form of the authored course
 */
function local_my_print_authored_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE;

    $renderer = $PAGE->get_renderer('local_my');

    $config = get_config('local_my');

    $mycourses = local_get_my_authoring_courses();

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if (!empty($id)) {
                unset($mycourses[$id]);
            }
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
    $str .= '<h2>'.get_string('myteachings', 'local_my').'</h2>';
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
        $excludedcourses = array_merge($excludedcourses, array_keys($mycourses));
=======
>>>>>>> MOODLE_34_STABLE
    }
    return $OUTPUT->render_from_template('local_my/courses_slider_module', $template);
}

/**
 * Prints the "classical" "My Courses" area for authors (needs having edition capabilities).
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_authored_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $CFG, $DB, $PAGE;

    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    $renderer = $PAGE->get_renderer('local_my');
    $myauthcourses = local_get_my_authoring_courses();
    $template = new StdClass;

    if (!empty($excludedcourses)) {
        $debuginfo = '';
        foreach ($excludedcourses as $cid) {
            if ($debug) {
                $debuginfo .= "rejected authored $cid as excluded</br/>";
            }
            unset($myauthcourses[$cid]);
        }
        if ($debug) {
            $template->debuginfo = $debuginfo;
        }
    }

    // Post 2.5.
    include_once($CFG->dirroot.'/lib/coursecatlib.php'); // Keep this here as being used after configi init.
    $mycatlist = coursecat::make_categories_list('moodle/course:create');

    $template->myauthoringcoursesstr = get_string('myauthoringcourses', 'local_my');

    $template->hascontent = false;
    if (!empty($mycatlist) || !empty($myauthcourses)) {
        $template->hascontent = true;
    }

    $template->buttons = $renderer->course_creator_buttons($mycatlist);

    if (!empty($myauthcourses)) {
        $template->hascourses = true;
        if (count($myauthcourses) < 0 + @$config->maxoverviewedlistsize) {
            $attrs = array('gaugewidth' => 0, 'gaugeheight' => 0);
            $template->overviewedcourses = $renderer->course_overview($myauthcourses, true, $attrs);
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
            $template->simplecourses = local_my_print_courses('myauthcourses', $myauthcourses, $options, true);
        }

        if (!empty($myauthcourses)) {
            foreach ($myauthcourses as $ac) {
                if ($debug) {
                    $debuginfo .= "exclude authored $ac->id as authored <br/>";
                }
                if (!in_array($ac->id, $excludedcourses)) {
                    $excludedcourses[] = $ac->id;
                }
            }
            if ($debug) {
                // Update debug info if necessary.
                $template->debuginfo = $debuginfo;
            }
        }
    }

    return $OUTPUT->render_from_template('local_my/authored_courses_module', $template);
}

/**
 * Prints the slider form of the authored course
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
<<<<<<< HEAD
function local_my_print_authored_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $CFG, $DB, $PAGE;
=======
function local_my_print_authored_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE;
>>>>>>> MOODLE_34_STABLE

    $renderer = $PAGE->get_renderer('local_my');

<<<<<<< HEAD
    $renderer = $PAGE->get_renderer('local_my');

    $myauthcourses = local_get_my_authoring_courses();
=======
    $config = get_config('local_my');
    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    $mycourses = local_get_my_authoring_courses();
>>>>>>> MOODLE_34_STABLE

    $debuginfo = '';
    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if (!empty($id)) {
                if ($debug) {
                    $debuginfo .= "rejected authored $cid as excluded</br/>";
                }
                unset($mycourses[$id]);
            }
        }
    }

<<<<<<< HEAD
<<<<<<< HEAD
    // Post 2.5.
    include_once($CFG->dirroot.'/lib/coursecatlib.php');
    $mycatlist = coursecat::make_categories_list('moodle/course:create');

    $str = '';
=======
    $debug = optional_param('debug', false, PARAM_BOOL);
>>>>>>> MOODLE_34_STABLE

=======
>>>>>>> MOODLE_34_STABLE
    foreach ($mycourses as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    $debuginfo .= "reject meta $id as meta disabled";
                }
                unset($mycourses[$id]);
                continue;
            }
        }
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

<<<<<<< HEAD
<<<<<<< HEAD
    $str .= $renderer->course_creator_buttons($mycatlist);

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
        $excludedcourses = array_merge($excludedcourses, array_keys($myauthcourses));
    }

    if ($hascontent) {
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}

/**
 * Prints a courses area for all teachers.
 */
function local_my_print_teacher_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE;

    $renderer = $PAGE->get_renderer('local_my');

    $config = get_config('local_my');

    $coursefields = 'shortname, fullname, category, visible';
    $teachercourses = get_user_capability_course('local/my:isteacher', $USER->id, false, $coursefields);
    $myteachercourses = array();
    if (!empty($teachercourses)) {
        // Key eahc course with id.
        foreach ($teachercourses as $c) {
            $myteachercourses[$c->id] = $c;
        }
    }

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if (!empty($id)) {
                unset($myteachercourses[$id]);
            }
        }
    }

    $debug = optional_param('debug', false, PARAM_BOOL);

    foreach ($myteachercourses as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    echo "reject meta $id as meta disabled";
                }
                unset($myteachercourses[$id]);
                continue;
            }
        }
        $myteachercourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $str = '';

    $str .= '<div class="block block_my_courses">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('myteachings', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';

    if (empty($myteachercourses)) {
=======
    $str = '';

    $str .= '<div class="block block_my_courses">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('myteachings', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';
=======
    $template = new StdClass;
    $template->widgetname = 'my_authored_courses_slider';
    $template->courselisttitlestr = get_string('myteachings', 'local_my');
>>>>>>> MOODLE_34_STABLE

    include_once($CFG->dirroot.'/lib/coursecatlib.php'); // Keep this here as being used after configi init.
    $mycatlist = coursecat::make_categories_list('moodle/course:create');
    if (!empty($mycatlist)) {
        $template->buttons = $renderer->course_creator_buttons($mycatlist);
    }

    if (empty($mycourses)) {
<<<<<<< HEAD
>>>>>>> MOODLE_34_STABLE
        $str .= '<table id="mycourselist" width="100%" class="courselist">';
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= get_string('nocourses', 'local_my');
        $str .= '</td>';
        $str .= '</tr>';
        $str .= '</table>';
    } else {
<<<<<<< HEAD
        $str .= $renderer->courses_slider(array_keys($myteachercourses));
        $excludedcourses = array_merge($excludedcourses, array_keys($myteachercourses));
    }

    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints a courses area for all teachers.
 */
function local_my_print_teacher_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $CFG, $DB, $USER, $PAGE;

    $debug = 0;

    $renderer = $PAGE->get_renderer('local_my');

=======
        $str .= $renderer->courses_slider(array_keys($mycourses));
=======
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
    } else {
        $template->hascourses = true;
        $template->courseslider = $renderer->courses_slider(array_keys($mycourses));
>>>>>>> MOODLE_34_STABLE
        $excludedcourses = array_merge($excludedcourses, array_keys($mycourses));
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_module('local_my/courses_slider_module', $template);
}

/**
 * Prints a courses area for all teachers (editors and non editors).
 * This will print a row of edition buttons if the treacher has capability to create course "somewhere" and
 * a list of courses with an edition/non edition signal.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_teacher_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $CFG, $DB, $USER, $PAGE;

    $debug = optional_param('debug', false, PARAM_BOOL) && ($CFG->debug >= DEBUG_ALL);

    $renderer = $PAGE->get_renderer('local_my');

>>>>>>> MOODLE_34_STABLE
    $coursefields = 'shortname, fullname, category, visible';
    $teachercourses = get_user_capability_course('local/my:isteacher', $USER->id, false, $coursefields);
    $myteachercourses = array();
    if (!empty($teachercourses)) {
        // Key eahc course with id.
        foreach ($teachercourses as $c) {
            $myteachercourses[$c->id] = $c;
        }
    }

    $debuginfo = '';
    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $cid) {
            if ($debug) {
                $debuginfo .= "rejected teached $cid as excluded</br/>";
            }
            unset($myteachercourses[$cid]);
        }
    }

    // Post 2.5.
<<<<<<< HEAD
    include_once($CFG->dirroot.'/lib/coursecatlib.php');
=======
    include_once($CFG->dirroot.'/lib/coursecatlib.php'); // Keep this here as being used after configi init.
>>>>>>> MOODLE_34_STABLE
    $mycatlist = coursecat::make_categories_list('moodle/course:create');

    $template = new StdClass;

    $template->hascontent = false;
    if (!empty($mycatlist) || !empty($myteachercourses)) {
        $template->myteachercoursesstr = get_string('myteachercourses', 'local_my');
        $template->hascontent = true;
    }

    $template->buttons = $renderer->course_creator_buttons($mycatlist);

    if (!empty($myteachercourses)) {
        $template->hascourses = true;
        if (count($myteachercourses) < 0 + @$config->maxoverviewedlistsize) {
            $attrs = array('gaugewidth' => 0, 'gaugeheight' => 0, 'asteacher' => true);
            $template->overviewedcourses = $renderer->course_overview($myteachercourses, true, $attrs);
        } else {
            if (count($myteachercourses) < (0 + @$config->maxuncategorizedlistsize)) {
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
            $template->simplecourses = local_my_print_courses('myauthcourses', $myteachercourses, $options, true);
        }

        if ($debug) {
            foreach ($myteachercourses as $ac) {
                $debuginfo .= "exclude course $ac->id as teaching course <br/>";
            }
        }
        $excludedcourses = array_merge($excludedcourses, array_keys($myteachercourses));
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_teacher_courses_module', $template);
}

/**
 * Prints a courses area for all teachers (editing and not editing) as a course slider.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_teacher_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE;

    $renderer = $PAGE->get_renderer('local_my');

    $config = get_config('local_my');

    $coursefields = 'shortname, fullname, category, visible';
    $teachercourses = get_user_capability_course('local/my:isteacher', $USER->id, false, $coursefields, 'sortorder ASC');
    $myteachercourses = array();
    if (!empty($teachercourses)) {
        // Key eahc course with id.
        foreach ($teachercourses as $c) {
            $myteachercourses[$c->id] = $c;
        }
    }

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if (!empty($id)) {
                unset($myteachercourses[$id]);
            }
        }
    }

    $debug = optional_param('debug', false, PARAM_BOOL);

    $debuginfo = '';
    foreach ($myteachercourses as $id => $c) {
        if (!empty($config->skipmymetas)) {
            if (local_my_is_meta_for_user($c->id, $USER->id)) {
                if ($debug) {
                    $debuginfo .= "reject meta $id as meta disabled";
                }
                unset($myteachercourses[$id]);
                continue;
            }
        }
        $myteachercourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $template = new Stdclass;
    $template->widgetname = 'teacher_courses_slider';

    $template->courselisttitlestr = get_string('myteachings', 'local_my');

    include_once($CFG->dirroot.'/lib/coursecatlib.php'); // Keep this here as being used after configi init.
    $mycatlist = coursecat::make_categories_list('moodle/course:create');
    if (!empty($mycatlist)) {
        $template->buttons = $renderer->course_creator_buttons($mycatlist);
    }

    if (empty($myteachercourses)) {
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
    } else {
        $template->courseslider = $renderer->courses_slider(array_keys($myteachercourses));
        $excludedcourses = array_merge($excludedcourses, array_keys($myteachercourses));
    }

    return $OUTPUT->render_from_template('local_my/course_slider_module', $template);
}

/**
 * Print a course list of 5(hardcoded) last visited courses.
 */
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

    $template = new StdClass;
    $template->widgetname = 'recent_courses';

    $fs = get_file_storage();

    if (!empty($recentcourses)) {
        $template->courselisttitlestr = get_string('recentcourses', 'local_my');

        foreach ($recentcourses as $c) {

            $context = context_course::instance($c->id);

            $coursetpl = new Stdclass;
            $coursetpl->courseurl = new moodle_url('/course/view.php?id='.$c->id);

            $coursetpl->css = $c->visible ? '' : 'dimmed';
            $coursetpl->fullname = format_string($fullname);
            $coursetpl->shortname = $shortname;

            $coursetpl->editingicon = $this->editing_icon($course);

            $images = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
            if ($image = array_pop($images)) {
                $coursefileurl = moodle_url::make_pluginfile_url($context->id, 'course', 'overviewfiles', '',
                                                                 $image->get_filepath(), $image->get_filename());
                $coursetpl->coursefileurl = $coursefileurl;
            } else {
                $coursetpl->summary = shorten_text(format_string($c->summary), 80);
            }
            $template->courses[] = $coursetpl;
        }

        return $OUTPUT->render_from_template('local_my/recent_courses_module', $template);
    }
}

/**
 * Prints the list of course templates that belongs to me.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
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

    include_once($CFG->dirroot.'/lib/coursecatlib.php'); // Keep this here as being used after configi init.
    $templatecatcontext = context_coursecat::instance($config->templatecategory);

    $canview = false;
    if ($mytemplates) {
        foreach ($mytemplates as $tid => $t) {
            $coursecontext = context_course::instance($t->id);
            if (has_capability('local/my:isauthor', $coursecontext)) {
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

    $template = new StdClass;

    $template->mytemplatesstr = get_string('mytemplates', 'local_my');

    if (is_dir($CFG->dirroot.'/local/coursetemplates')) {
        $config = get_config('local_coursetemplates');
        if ($config->enabled && $config->templatecategory) {
            if ($DB->count_records('course', array('category' => $config->templatecategory, 'visible' => 1))) {
                $params = array('category' => $config->templatecategory, 'forceediting' => true);
                $buttonurl = new moodle_url('/local/coursetemplates/index.php', $params);
                $template->button1 = $OUTPUT->single_button($buttonurl, get_string('newtemplate', 'local_my'));
            } else {
                $template->button1 = get_string('templateinitialisationadvice', 'local_my');
            }
        }
    }

    if (!empty($mytemplates)) {
        $template->templates = local_my_print_courses('mytemplates', $mytemplates, array('noheading' => 1, 'nocompletion' => 1));

<<<<<<< HEAD
        $excludedcourses = array_merge($excludedcourses, array_keys($mytemplates));
=======
        // Add templates to exclusions.
        foreach (array_keys($mytemplates) as $tplid) {
            if (!in_array($tplid, $excludedcourses)) {
                $excludedcourses[] = $tplid;
            }
        }
>>>>>>> MOODLE_34_STABLE
    }

    return $OUTPUT->render_from_template('local_my/my_templates_module', $template);
}

/**
 * Prints the specific courses area as a 3 column link list. Courses not enrolled will not appear here.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_course_areas(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $DB, $PAGE, $USER;

    // Get all courses i am in.
    $allcourses = enrol_get_my_courses('id, shortname, fullname');

    $config = get_config('local_my');
    $renderer = $PAGE->get_renderer('local_my');

    $options = array();
    $options['withcats'] = 0;

    // Ensure we have last access.
    foreach ($allcourses as $id => $c) {
        $params = array('userid' => $USER->id, 'courseid' => $id);
        $allcourses[$id]->lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', $params);
    }

    if (empty($config->courseareas)) {
        // Performance quick trap if no areas defined at all.
        return;
    }

    $template = new StdClass;

    $reali = 1;
    for ($i = 0; $i < $config->courseareas; $i++) {

        $key = 'coursearea'.$i;

        if (empty($config->$key)) {
            continue;
        }

        $categoryid = $config->$key;

        $mastercategory = $DB->get_record('course_categories', array('id' => $categoryid));
        if (!$mastercategory) {
            continue;
        }

        // Filter courses of this area.
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        $areacourses = array();
        foreach ($allcourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $areacourses[$c->id] = $c;
                $excludedcourses[] = $c->id;
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

            $courseareatpl = new StdClass;

            if ($reali % 3 == 0) {
                $courseareatpl->coljump = true;
            }
            $courseareatpl->colwidth = $colwidth;
            $courseareatpl->catname = $mastercategory->name;
            $courseareatpl->i = $reali;

            if (count($areacourses) < $config->maxoverviewedlistsize) {
                $params = array('gaugewidth' => 50, 'gaugeheight' => 12);
                $courseareatpl->courseoverview = $renderer->course_overview($areacourses, $params);
            } else {
                // Solve a performance issue for people having wide access to courses.
                $courseareatpl->coursesbycats = $renderer->courses_by_cats($areacourses, $options, 'courseareas');
            }

            $template->courseareas[] = $courseareatpl;

            $reali++;
        }
    }

    return $OUTPUT->render_from_template('local_my/courseareas_module', $template);
}

/**
 * Prints the specific courses area as a 3 column link list, adding also courses in areas that
 * are self enrollable.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_course_areas_and_availables(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $DB, $PAGE;

    $debug = 0;

    $mycourses = enrol_get_my_courses('id,shortname,fullname');
    $availablecourses = local_get_enrollable_courses();
    $config = get_config('local_my');

    $renderer = $PAGE->get_renderer('local_my');

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
        foreach (array_keys($excludedcourses) as $cid) {
            if (in_array($cid, array_keys($courseareacourses))) {
                continue;
            }
            if ($debug) {
                echo "reject enrolled as excluded $cid <br/>";
            }
            if (array_key_exists($cid, $mycourses)) {
                unset($mycourses[$cid]);
            }
            if (array_key_exists($cid, $availablecourses)) {
                if ($debug) {
                    echo "reject available as excluded $id <br/>";
                }
                unset($availablecourses[$cid]);
            }
        }
    }

    foreach ($mycourses as $cid => $c) {
        // TODO Add logger selection.
<<<<<<< HEAD
        $mycourses[$cid]->lastaccess = $DB->get_field('logstore_standard_log', 'max(timemodified)', array('course' => $cid));
=======
        $mycourses[$cid]->lastaccess = $DB->get_field('logstore_standard_log', 'max(timecreated)', array('courseid' => $cid));
>>>>>>> MOODLE_34_STABLE
    }

    $template = new StdClass;

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

        // Filter courses of this area.
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        $myareacourses = array();
        foreach ($mycourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $myareacourses[$c->id] = $c;
                if (!in_array($c->id, $excludedcourses)) {
                    $excludedcourses[] = $c->id;
                }
            }
        }

        $availableareacourses = array();
        foreach ($availablecourses as $c) {
            if (!isset($c->summary)) {
                $c->summary = $DB->get_field('course', 'summary', array('id' => $id));
            }
            if (in_array($c->category, $retainedcategories)) {
                $availableareacourses[$c->id] = $c;
                if (!in_array($c->id, $excludedcourses)) {
                    $excludedcourses[] = $c->id;
                }
            }
        }

        if (!empty($myareacourses) || !empty($availableareacourses)) {
            $courseareatpl = new StdClass;
            if ($reali % 3 == 0) {
                $courseareatpl->coljump = true;
            }
            $courseareatpl->catname = format_string($mastercategory->name);
            $courseareatpl->i = $reali;

            if (empty($options['nooverview'])) {
                if (count($myareacourses) < $config->maxoverviewedlistsize) {
                    $courseareatpl->courseoverview = $renderer->course_overview($myareacourses, $options);
                } else {
                    // Solve a performance issue for people having wide access to courses.
                    $courseareatpl->coursesbycats = local_print_courses_by_cats($myareacourses, $options);
                }
            } else {
                // Aggregate my courses with the available and print in one unique list.
                $availableareacourses = array_merge($myareacourses, $availableareacourses);
            }
            if (!empty($availableareacourses)) {
                $courseareatpl->availables = local_my_print_courses('available', $availableareacourses, $options);
            }

            $template->courseareas[] = $courseareatpl;

            $reali++;
        }
    }

    return $OUTPUT->render_from_template('local_my/courseareas_module', $template);
}

/**
 * Prints the available (enrollable) courses as simple link entries
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_available_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT;

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

    foreach ($courses as $cid => $foo) {
        if (in_array($cid, $excludedcourses)) {
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

    $template = new StdClass;

    $template->availablecourses = local_my_print_courses('availablecourses', $courses, $options);
    if ($overcount) {
        $template->hastoomany = true;
        $template->allcoursesurl = new moodle_url('/local/my/enrollable_courses.php');
        $template->seeallliststr = get_string('seealllist', 'local_my');
    }

    return $OUTPUT->render_from_template('local_my/available_courses_module', $template);
}

/**
 * Prints the news forum as a list of full deployed discussions.
 */
function local_my_print_latestnews_full() {
    global $SITE, $CFG, $SESSION, $USER;

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // Fetch news forum context for proper filtering to happen.
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $template = new StdClass;

        $template->forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));

        if (isloggedin()) {
            $SESSION->fromdiscussion = $CFG->wwwroot;
            if (\mod_forum\subscriptions::is_subscribed($USER->id, $newsforum)) {
                if (!\mod_forum\subscriptions::is_forcesubscribed($newsforum)) {
                    $template->subscribestr = get_string('unsubscribe', 'forum');
                }
            } else {
                $template->subscribestr = get_string('subscribe', 'forum');
            }
            $params = array('id' => $newsforum->id, 'sesskey' => sesskey());
            $template->subscribeurl = new moodle_url('/mod/forum/subscribe.php', $params);
            $template->isloggedin = true;
        }

        // Need capture HTML raw output.
        ob_start();
        forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'plain', 'p.modified DESC');
        $template->lastdiscussions .= ob_get_clean();

        return $OUTPUT->render_from_template('local_my/latest_news_module', $template);
    }

    return '';
}

/**
 * Prints the news forum as simple compact list of discussion headers.
 */
function local_my_print_latestnews_headers() {
    global $PAGE, $SITE, $CFG, $OUTPUT, $USER, $SESSION;

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // Fetch news forum context for proper filtering to happen.
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $template = new StdClass;

        $template->forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));

        if (isloggedin()) {
            if (!isset($SESSION)) {
                $SESSION = new StdClass();
            }
            $SESSION->fromdiscussion = $CFG->wwwroot;

            // Convert name to link.
            $renderer = $PAGE->get_renderer('local_my');
            $template->forumname = $renderer->print_forum_link($newsforum, $forumname);

            if (\mod_forum\subscriptions::is_subscribed($USER->id, $newsforum)) {
                if (!\mod_forum\subscriptions::is_forcesubscribed($newsforum)) {
                    $template->subscribestr = get_string('unsubscribe', 'forum');
                }
            } else {
                $template->subscribestr = get_string('subscribe', 'forum');
            }
            $params = array('id' => $newsforum->id, 'sesskey' => sesskey());
            $template->subscribeurl = new moodle_url('/mod/forum/subscribe.php', $params);
        }

        ob_start();
        forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'header', 'p.modified DESC');
        $template->lastdiscussions = ob_get_clean();

        return $OUTPUT->render_from_template('local_my/latest_news_module', $template);
    }

    return $str;
}

/**
 * Same as "full", but removes all subscription or any discussion controls.
 */
function local_my_print_latestnews_simple() {
    global $PAGE, $SITE, $CFG, $OUTPUT, $DB, $SESSION;

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot .'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        $renderer = $PAGE->get_renderer('local_my');

        $template = new StdClass;
        $template->forumname = format_string($newsforum->name);
        $template->simple = true;

        $newsdiscussions = $DB->get_records('forum_discussions', array('forum' => $newsforum->id), 'timemodified DESC');
        foreach ($newsdiscussions as $news) {
            $discussiontpl = new StdClass;
            $discussiontpl->discussionurl = new moodle_url('/mod/forum/discuss.php', array('d' => $news->id));
            $discussiontpl->newstitle = format_string($news->name);
            $discussiontpl->timemodified = userdate($news->timemodified);
            $template->discussions[] = $discussiontpl;
        }
        $template->forumlink = $renderer->print_forum_link($newsforum, $newsforum->name);

        return $OUTPUT->render_from_template('local_my/latest_news_module', $template);
    }

    return '';
}

/**
 * Prints a static div with content stored into central configuration.
 * If index points to a recognizable profile field, will check the current user
 * profile field to display.
 */
function local_my_print_static($index) {
    global $CFG, $DB, $USER, $OUTPUT;

    if (!file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
        return $OUTPUT->notification(get_string('nostaticguitexts', 'local_my', 'static'));
    }

    $context = context_system::instance();
    $template = new StdClass;

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
        $template->staticindex = $index;
        $template->staticclass = $class;

        if ($class == 'adminview') {
            $e = new StdClass;
            $e->field = $field->name;
            $e->value = $profileexpectedvalue;
            $template->adminviewstr = get_string('adminview', 'local_my', $e);
            $template->hasadminview = true;
        }
        $template->statictext = local_print_static_text('custommystaticarea_'.$index, $CFG->wwwroot.'/my/index.php', '', true);

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
                return $OUTPUT->notification(get_string('fieldnotfound', 'local_my', $fieldname));
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

            $template->hasadminview = true;

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

                $modoptions = array();

                $visibilityclass = '';

                foreach ($modalities as $modality) {

                    $modaltpl = new StdClass;

                    // Reformat key for token integrity.
                    if (is_object($modality)) {
                        $modality = core_text::strtolower($modality->data);
                    } else {
                        $modality = core_text::strtolower($modality);
                    }
                    $modality = trim($modality);
                    $modality = str_replace(' ', '-', $modality);
                    $modalindex = $index.'-'.$modality;

                    $modaltpl->index = $modalindex;
                    $a = new StdClass;
                    $a->profile = $field->shortname;
                    $a->data = $modality;
                    $modaltpl->contentforstr = get_string('contentfor', 'local_my', $a);
                    $return = new moodle_url('/my/index.php');
                    $modaltpl->statictext = local_print_static_text('custommystaticarea-'.$modalindex, $return, '', true);
                    $modaltpl->visibilityclass = $visibilityclass;
                    $template->modalities[] = $modaltpl;

                    $visibilityclass = 'local-my-hide';

                    $modoptions[$modality] = $modality;
                }

                // Choose first as active.
                $template->modalitiesselect = html_writer::select($modoptions, 'modalities', array_keys($modoptions)[0]);

            }
        } else {
            // Normal user, one sees his own.

            $modaltpl = new StdClass;
            $modaltpl->modalindex = $index.'-'.$profilevalue;

            $return = new moodle_url('/my/index.php');
            $modaltpl->statictext = local_print_static_text('custommystaticarea-'.$index, $return, '', true);
            $template->modalities[] = $modaltpl;
        }
    }

    return $OUTPUT->render_from_template('local_my/static_module', $template);
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
    global $CFG, $USER, $OUTPUT;

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
    $str .= $OUTPUT->box_start('my-modules heatmap');

    $str .= $OUTPUT->box_start('block block_my_heatmap');

    $str .= $OUTPUT->box_start('header');
    $str .= $OUTPUT->box_start('title');
    $str .= '<h2 >'.get_string('myactivity', 'local_my').'</h2>';
    $str .= $OUTPUT->box_end();
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_start('content');

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

    $str .= $OUTPUT->box_end(); // Content.

    $str .= $OUTPUT->box_end(); // Block.
    $str .= $OUTPUT->box_end(); // Module.

    return $str;
}

/**
 * Prints a module that is the content of the user_mnet_hosts block.
 */
function local_my_print_my_network() {

    $blockinstance = block_instance('user_mnet_hosts');
    if (empty($blockinstance)) {
        // If user mnet hosts even not installed.
        return;
    }

    $content = $blockinstance->get_content();

    $str = '';

    if (!empty($content->items) || !empty($content->footer)) {

        $str .= '<div class="my-modules network">';
        $str .= '<div class="box block block_user_mnet_hosts">';
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
    global $PAGE, $OUTPUT;

    $renderer = $PAGE->get_renderer('course');

    $str = '';

    $search = optional_param('search', '', PARAM_TEXT);

    $str .= $OUTPUT->box_start('my-modules admin-stats');

    $str .= $OUTPUT->box_start('box block');
<<<<<<< HEAD
=======

>>>>>>> MOODLE_34_STABLE
    $str .= $OUTPUT->box_start('header');
    $str .= $OUTPUT->box_start('title');
    $str .= '<h2 class="headingblock header">'.get_string('coursesearch', 'local_my').'</h2>';
    $str .= $OUTPUT->box_end();
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_start('content');
    $str .= $renderer->course_search_form($search, 'plain');
    $str .= $OUTPUT->box_end();
<<<<<<< HEAD
=======

>>>>>>> MOODLE_34_STABLE
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_end();

    return $str;
}

function local_my_print_admin_stats() {
    global $PAGE, $OUTPUT;

    $renderer = $PAGE->get_renderer('local_my');

    $str = '';

    $str .= $OUTPUT->box_start('my-modules admin-stats');

    $str .= $OUTPUT->box_start('box block');
    $str .= $OUTPUT->box_start('header');
    $str .= $OUTPUT->box_start('title');
    $str .= '<h2>'.get_string('sitestats', 'local_my').'</h2>';
    $str .= $OUTPUT->box_end();
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_start('content');
    $str .= $renderer->site_stats();
    $str .= $OUTPUT->box_end();
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_end();

    return $str;
}
