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
require_once($CFG->dirroot.'/local/my/lib.php');

/**
 * Prints the "classical" "My Courses" area, course for students that are not displayed elsewhere.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
// TRANSCODED
function local_my_print_my_courses(&$excludedcourses, &$courseareacourses, $required = 'aslist') {
    global $DB, $USER, $OUTPUT, $PAGE, $CFG;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass;

    $mycourses = local_my_get_my_courses($debuginfo, $excludedcourses, $courseareacourses);

    // Default gauge settings.
    $options = [
        'gaugetype' => $config->progressgaugetype,
        'gaugewidth' => $config->progressgaugewidth,
        'gaugeheight' => $config->progressgaugeheight
    ];

    if (!empty($config->effect_opacity)) {
        $template->withopacityeffect = 'with-opacity-effect';
    }

    if (!empty($config->effect_halo)) {
        $template->withhaloeffect = 'with-halo-effect';
    }

    $template->area = 'my_courses';
    $template->modulename = get_string('mycourses', 'local_my');

    if (empty($mycourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }

    $template->hascourses = true;
    $template->required = $required;

    local_my_resolve_viewtype($template, count($mycourses));

    if ($template->asflatlist || $template->asgrid) {

        if ($template->asflatlist) {
            $options['gaugetype'] = 'sektor';
            $options['gaugewidth'] = '20';
            $options['gaugeheight'] = '20';
        } else {
            $options['gaugetype'] = $config-progressgaugetype;
            $options['gaugewidth'] = $config-progressgaugewidth;
            $options['gaugeheight'] = $config-progressgaugeheight;
        }

        // Get a simple, one level list.
        foreach ($mycourses as $cid => $c) {
            $coursetpl = local_my_export_course_for_template($c, $options);
            $template->courses[] = $coursetpl;
        }
    } else {
        // as list.
        $template->isaccordion = !empty($config->courselistaccordion);
        $options['gaugetype'] = 'sektor';
        $options['gaugewidth'] = '20';
        $options['gaugeheight'] = '20';
        $result = local_my_export_courses_cats_for_template($courselist, $options);
        $template->categories = $result->categories;
        $template->catidlist = $result->catidlist;
    }

    // Process exclusion of what has been displayed.
    $debuginfo .= local_my_exclude_post_display($mycourses, $excludedcourses, 'mycourses');

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
}

// TRANSCODED
function local_my_print_my_courses_grid(&$excludedcourses, &$courseareacourses) {
    return local_my_print_my_courses($excludedcourses, $courseareacourses, ['asgrid' => true]);
}

// TRANSCODED
function local_my_print_main_content_anchor(&$excludedcourses, &$courseareacourses) {
    return '<a name="localmymaincontent"></a>';
}

/**
 * Prints the slider form of "My Courses" area, that is, courses i'm stdying in.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_my_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE, $CFG, $OUTPUT;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass();

    $mycourses = local_my_get_my_courses($debuginfo, $excludedcourses, $courseareacourses);

    $template->widgetname = 'my_courses_slider';

    $template->courselisttitlestr = get_string('mycourses', 'local_my');
    $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));

    if (empty($mycourses)) {
        $template->hascourses = false;
    } else {
        $template->hascourses = true;
        $template->courseslider = $renderer->courses_slider(array_keys($mycourses));
        $debuginfo .= local_my_exclude_post_display($mycourses, $excludedcourses, 'mycourseslider');
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/courses_slider_module', $template);
}

/**
 * Prints the "classical" "My Courses" area for authors (needs having edition capabilities).
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
 // TRANSCODED
function local_my_print_authored_courses(&$excludedcourses, &$courseareacourses, $required = 'aslist') {
    global $OUTPUT, $CFG, $DB, $PAGE;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass();

    $myauthcourses = local_get_my_authoring_courses($debuginfo, $excludedcourses, $courseareacourses);

    // Default gauge settings.
    $options = [
        'withteachersignals' => true,
        'gaugetype' => $config->progressgaugetype,
        'gaugewidth' => $config->progressgaugewidth,
        'gaugeheight' => $config->progressgaugeheight
    ];

    if (!empty($config->effect_opacity)) {
        $template->withopacityeffect = 'with-opacity-effect';
    }

    if (!empty($config->effect_halo)) {
        $template->withhaloeffect = 'with-halo-effect';
    }

    $mycatlist = local_my_get_catlist('moodle/course:create');
    $template->buttons = $renderer->course_creator_buttons($mycatlist);
    $template->area = 'authored_courses';
    $template->modulename = get_string('myauthoringcourses', 'local_my');

    if (empty($mycatlist) && empty($myauthcourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }
    if (empty($myauthcourses) && empty($template->buttons)) {
        // In case we cannot create and all courses where gone elsewhere.
        return '';
    }

    $template->hascourses = true;
    $template->required = $required;

    local_my_resolve_viewtype($template, count($myauthcourses));

    if (!empty($template->asflatlist) || !empty($template->asgrid)) {

        if (!empty($template->asflatlist)) {
            $options['gaugetype'] = 'sektor';
            $options['gaugewidth'] = '20';
            $options['gaugeheight'] = '20';
        } else {
            $options['gaugetype'] = $config-progressgaugetype;
            $options['gaugewidth'] = $config-progressgaugewidth;
            $options['gaugeheight'] = $config-progressgaugeheight;
        }

        $options['noprogress'] = true;

        // Get a simple, one level list.
        foreach ($myauthcourses as $cid => $c) {
            $coursetpl = local_my_export_course_for_template($c, $options);
            $template->courses[] = $coursetpl;
        }

    } else {
        // as list.
        $template->isaccordion = !empty($config->courselistaccordion);
        $options['gaugetype'] = 'sektor';
        $options['gaugewidth'] = '20';
        $options['gaugeheight'] = '20';
        $result = local_my_export_courses_cats_for_template($myauthcourses, $options);
        $template->categories = $result->categories;
        $template->catidlist = $result->catidlist;
    }


    $debuginfo .= local_my_exclude_post_display($myauthcourses, $excludedcourses, 'authored');

    if ($debug) {
        // Update debug info if necessary.
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
}

function local_my_print_authored_courses_grid(&$excludedcourses, &$courseareacourses) {
    return local_my_print_authored_courses($excludedcourses, $courseareacourses, ['asgrid' => true]);
}

/**
 * Prints the "classical" "My Courses" area for authors (needs having edition capabilities).
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
 // TRANSCODED
function local_my_print_managed_courses(&$excludedcourses, &$courseareacourses, $required = 'aslist') {
    global $OUTPUT, $PAGE;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass();

    $mymanagedcourses = local_get_my_managed_courses($debuginfo);

    // Default gauge settings.
    $options = [
        'withteachersignals' => true,
        'gaugetype' => $config->progressgaugetype,
        'gaugewidth' => $config->progressgaugewidth,
        'gaugeheight' => $config->progressgaugeheight
    ];

    if (!empty($config->effect_opacity)) {
        $template->withopacityeffect = 'with-opacity-effect';
    }

    if (!empty($config->effect_halo)) {
        $template->withhaloeffect = 'with-halo-effect';
    }

    $mycatlist = local_my_get_catlist('moodle/course:create');
    $template->buttons = $renderer->course_creator_buttons($mycatlist);

    $template->area = 'managed_courses';
    $template->modulename = get_string('mymanagedcourses', 'local_my');

    if (empty($mycatlist) && empty($mymanagedcourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }
    if (empty($mymanagedcourses) && empty($template->buttons)) {
        // In case we cannot create and all courses where gone elsewhere.
        return '';
    }

    $template->hascourses = true;
    $template->required = $required;

    local_my_resolve_viewtype($template, count($mymanagedcourses));

    if (!empty($template->asflatlist) || !empty($template->asgrid)) {

        if (!empty($template->asflatlist)) {
            $options['gaugetype'] = 'sektor';
            $options['gaugewidth'] = '20';
            $options['gaugeheight'] = '20';
        } else {
            $options['gaugetype'] = $config-progressgaugetype;
            $options['gaugewidth'] = $config-progressgaugewidth;
            $options['gaugeheight'] = $config-progressgaugeheight;
        }

        // Get a simple, one level list.
        foreach ($mymanagedcourses as $cid => $c) {
            $coursetpl = local_my_export_course_for_template($c, $options);
            $template->courses[] = $coursetpl;
        }

    } else {
        // as list.
        $template->isaccordion = !empty($config->courselistaccordion);
        $options['gaugetype'] = 'sektor';
        $options['gaugewidth'] = '20';
        $options['gaugeheight'] = '20';
        $result = local_my_export_courses_cats_for_template($mymanagedcourses, $options);
        $template->categories = $result->categories;
        $template->catidlist = $result->catidlist;
    }


    $debuginfo .= local_my_exclude_post_display($myauthcourses, $excludedcourses, 'authored');

    if ($debug) {
        // Update debug info if necessary.
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
}

function local_my_print_managed_courses_grid(&$excludedcourses, &$courseareacourses) {
    return local_my_print_managed_courses($excludedcourses, $courseareacourses, ['asgrid' => true]);
}

/**
 * Prints the slider form of the authored course
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_authored_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $PAGE, $OUTPUT;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass();

    $authoredcourses = local_get_my_authoring_courses($debuginfo, $excludedcourses);

    $template->widgetname = 'my_authored_courses_slider';
    $template->courselisttitlestr = get_string('myteachings', 'local_my');

    $mycatlist = local_my_get_catlist('moodle/course:create');

    if (!empty($mycatlist)) {
        $template->buttons = $renderer->course_creator_buttons($mycatlist);
    }

    if (empty($authoredcourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
    } else {
        $template->hascourses = true;
        $template->courseslider = $renderer->courses_slider(array_keys($authoredcourses));
        $debuginfo .= local_my_exclude_post_display($authoredcourses, $excludedcourses, 'authorslider');
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/courses_slider_module', $template);
}

/**
 * Prints the slider form of the managed course
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_managed_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $PAGE, $OUTPUT;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass;

    $mymanagedcourses = local_get_my_managed_courses($debuginfo, $excludedcourses);

    $template->widgetname = 'my_managed_courses_slider';
    $template->courselisttitlestr = get_string('mymanagedcourses', 'local_my');

    $mycatlist = local_my_get_catlist('moodle/course:create');

    if (!empty($mycatlist)) {
        $template->buttons = $renderer->course_creator_buttons($mycatlist);
    }

    if (empty($mymanagedcourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
    } else {
        $template->hascourses = true;
        $template->courseslider = $renderer->courses_slider(array_keys($mymanagedcourses));
        $debuginfo .= local_my_exclude_post_display($mymanagedcourses, $excludedcourses, 'managedslider');
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/courses_slider_module', $template);
}

/**
 * Prints a courses area for all teachers (editors and non editors).
 * This will print a row of edition buttons if the treacher has capability to create course "somewhere" and
 * a list of courses with an edition/non edition signal.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_teacher_courses(&$excludedcourses, &$courseareacourses, $required = 'aslist') {
    global $OUTPUT, $PAGE;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass();

    $coursefields = 'shortname, fullname, category, visible';
    $myteachercourses = local_my_get_myteacher_courses($debuginfo, $excludedcourses, $coursefields);

    // Default gauge settings.
    $options = [
        'withteachersignals' => true,
        'gaugetype' => $config->progressgaugetype,
        'gaugewidth' => $config->progressgaugewidth,
        'gaugeheight' => $config->progressgaugeheight
    ];

    if (!empty($config->effect_opacity)) {
        $template->withopacityeffect = 'with-opacity-effect';
    }

    if (!empty($config->effect_halo)) {
        $template->withhaloeffect = 'with-halo-effect';
    }

    $mycatlist = local_my_get_catlist('moodle/course:create');
    $template->buttons = $renderer->course_creator_buttons($mycatlist);

    $template->area = 'teacher_courses';
    $template->modulename = get_string('myteachercourses', 'local_my');

    if (empty($mycatlist) && empty($myteachercourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }
    if (empty($myteachercourses) && empty($template->buttons)) {
        // In case we cannot create and all courses where gone elsewhere.
        return '';
    }

    $template->hascourses = true;
    $template->required = $required;

    local_my_resolve_viewtype($template, count($myteachercourses));

    if (!empty($template->asflatlist) || !empty($template->asgrid)) {

        if (!empty($template->asflatlist)) {
            $options['gaugetype'] = 'sektor';
            $options['gaugewidth'] = '20';
            $options['gaugeheight'] = '20';
        } else {
            $options['gaugetype'] = $config-progressgaugetype;
            $options['gaugewidth'] = $config-progressgaugewidth;
            $options['gaugeheight'] = $config-progressgaugeheight;
        }

        // Get a simple, one level list.
        foreach ($myteachercourses as $cid => $c) {
            $coursetpl = local_my_export_course_for_template($c, $options);
            $template->courses[] = $coursetpl;
        }

    } else {
        // as list.
        $template->isaccordion = !empty($config->courselistaccordion);
        $options['gaugetype'] = 'sektor';
        $options['gaugewidth'] = '20';
        $options['gaugeheight'] = '20';
        $result = local_my_export_courses_cats_for_template($myteachercourses, $options);
        $template->categories = $result->categories;
        $template->catidlist = $result->catidlist;
    }


    $debuginfo .= local_my_exclude_post_display($myteachercourses, $excludedcourses, 'authored');

    if ($debug) {
        // Update debug info if necessary.
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
}

function local_my_print_teacher_courses_grid(&$excludedcourses, &$courseareacourses) {
    return local_my_print_teacher_courses($excludedcourses, $courseareacourses, 'asgrid');
}

/**
 * Prints a courses area for all teachers (editing and not editing) as a course slider.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
function local_my_print_teacher_courses_slider(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $PAGE, $CFG, $OUTPUT;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');

    $config = get_config('local_my');

    $coursefields = 'shortname, fullname, category, visible';
    $teachercourses = local_get_user_capability_course('local/my:isteacher', $USER->id, false, $coursefields, 'c.sortorder ASC');
    $myteachercourses = array();
    if (!empty($teachercourses)) {
        // Key each course with id.
        foreach ($teachercourses as $c) {
            $myteachercourses[$c->id] = $c;
            if ($debug == 1 || $debug == $c->id) {
                $debuginfo .= "Course Add (course $c->id as teached)\n";
            }
        }
    }

    $debuginfo .= local_my_process_excluded($excludedcourses, $myteachercourses);
    $debuginfo .= local_my_process_metas($myteachercourses);

    $template = new Stdclass();
    $template->widgetname = 'teacher_courses_slider';

    $template->courselisttitlestr = get_string('myteachings', 'local_my');

    $mycatlist = local_my_get_catlist('moodle/course:create');

    if (!empty($mycatlist)) {
        $template->buttons = $renderer->course_creator_buttons($mycatlist);
    }

    if (empty($myteachercourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
    } else {
        $template->hascourses = true;
        $template->courseslider = $renderer->courses_slider(array_keys($myteachercourses));
        $debuginfo .= local_my_exclude_post_display($myteachercourses, $excludedcourses, 'teachedslider');
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/courses_slider_module', $template);
}

/**
 * Print a course list of 5(hardcoded) last visited courses.
 */
 // TRANSCODED
function local_my_print_recent_courses() {
    global $DB, $USER, $PAGE, $OUTPUT;

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

    $template = new StdClass();
    $template->area = 'recent_courses';
    $template->modulename = get_string('recentcourses', 'local_my');

    $fs = get_file_storage();

    if (!empty($recentcourses)) {

        foreach ($recentcourses as $c) {

            $context = context_course::instance($c->id);

            $coursetpl = new Stdclass();
            $coursetpl->courseurl = new moodle_url('/course/view.php?id='.$c->id);

            $coursetpl->css = $c->visible ? '' : 'dimmed';
            $coursetpl->fullname = format_string($c->fullname);
            $coursetpl->shortname = $c->shortname;

            $coursetpl->editingicon = $renderer->editing_icon($c);

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
 // TRANSCODED
function local_my_print_my_templates(&$excludedcourses, &$courseareacourses) {
    global $DB, $USER, $OUTPUT, $PAGE, $CFG;

    if (!is_dir($CFG->dirroot.'/local/coursetemplates')) {
        // Short path if even not installed.
        return '';
    }

    $tconfig = get_config('local_coursetemplates');
    if ($tconfig->enabled) {
        // Short path if even not enabled.
        return '';
    }

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass;

    $mytemplates = local_get_my_templates($debuginfo, $excludedcourses);

    // Default gauge settings.
    $options = [
        'gaugetype' => $config->progressgaugetype,
        'gaugewidth' => $config->progressgaugewidth,
        'gaugeheight' => $config->progressgaugeheight
    ];

    if (!empty($config->effect_opacity)) {
        $template->withopacityeffect = 'with-opacity-effect';
    }

    if (!empty($config->effect_halo)) {
        $template->withhaloeffect = 'with-halo-effect';
    }

    $template->area = 'my_templates';
    $template->modulename = get_string('mytemplates', 'local_my');

    $templatecatcontext = context_coursecat::instance($tconfig->templatecategory);
    if (has_capability('moodle/course:create', $templatecatcontext, $USER->id, true)) {
        $canview = true;
    }

    // See if there is an exiting template course to intanciate other templates.
    $systemcontext = context_system::instance();
    if ($DB->count_records('course', array('category' => $tconfig->templatecategory, 'visible' => 1))) {
        $params = array('category' => $config->templatecategory, 'forceediting' => true);
        $buttonurl = new moodle_url('/local/coursetemplates/index.php', $params);
        $template->button = $OUTPUT->single_button($buttonurl, get_string('newtemplate', 'local_my'));
    } else if (has_capability('moodle/site:config', $systemcontext)) {
        $template->button = get_string('templateinitialisationadvice', 'local_my');
    }

    if (empty($mytemplates) && empty($template->button)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }

    $template->hascourses = true;
    $template->required = $required;

    local_my_resolve_viewtype($template, count($mytemplates));

    if ($template->asflatlist || $template->asgrid) {

        if ($template->asflatlist) {
            $options['gaugetype'] = 'sektor';
            $options['gaugewidth'] = '20';
            $options['gaugeheight'] = '20';
        } else {
            $options['gaugetype'] = $config-progressgaugetype;
            $options['gaugewidth'] = $config-progressgaugewidth;
            $options['gaugeheight'] = $config-progressgaugeheight;
        }

        // Get a simple, one level list.
        foreach ($mytemplates as $cid => $c) {
            $coursetpl = local_my_export_course_for_template($c, $options);
            $template->courses[] = $coursetpl;
        }
    } else {
        // as list.
        $template->isaccordion = !empty($config->courselistaccordion);
        $options['gaugetype'] = 'sektor';
        $options['gaugewidth'] = '20';
        $options['gaugeheight'] = '20';
        $result = local_my_export_courses_cats_for_template($mytemplates, $options);
        $template->categories = $result->categories;
        $template->catidlist = $result->catidlist;
    }

    // Process exclusion of what has been displayed.
    $debuginfo .= local_my_exclude_post_display($mytemplates, $excludedcourses, 'mytemplates');

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
}

/**
 * Prints the specific courses area as a 3 column link list. Courses not enrolled will not appear here.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
 // TRANSCODED
function local_my_print_course_areas(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $DB, $PAGE, $USER;

    $config = get_config('local_my');
    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';

    if (empty($config->courseareas)) {
        // Performance quick trap if no areas defined at all.
        return;
    }

    // Get all courses i am in.
    $allcourses = enrol_get_my_courses('id, shortname, fullname');

    $renderer = $PAGE->get_renderer('local_my');

    $options = array();
    $options['withcats'] = $config->printcategories;
    $options['gaugewidth'] = 60;
    $options['gaugeheight'] = 15;

    // Ensure we have last access.
    foreach ($allcourses as $id => $c) {
        $params = array('userid' => $USER->id, 'courseid' => $id);
        $allcourses[$id]->lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', $params);
    }

    list($view, $isstudent, $isteacher, $iscoursemanager) = local_my_resolve_view();
    $template = new StdClass();

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
                $colwidth = 32;
        }
    }

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

            $context = context_course::instance($c->id);
            // Treat site admins as standard users.
            // $editing = has_capability('moodle/course:manageactivities', $context, $USER, false);
            $hasteachingactivity = has_capability('local/my:isteacher', $context, $USER, false);
            $hasmanageractivity = has_capability('local/my:iscoursemanager', $context, $USER, false);

            // Filter out non editing.
            if ($view == 'asteacher') {
                if (!$hasteachingactivity) {
                    continue;
                }
            } else if ($view == 'ascoursemanager') {
                if (!$hasmanageractivity) {
                    continue;
                }
            } else {
                if ($hasteachingactivity || $hasmanageractivity) {
                    continue;
                }
            }

            if (in_array($c->category, $retainedcategories)) {
                $areacourses[$c->id] = $c;
                if ($debug == 1 || $debug == $c->id) {
                    $debuginfo .= "Course Add ({$c->id} in course area $key\n";
                }
                $excludedcourses[] = $c->id;
                if ($debug == 1 || $debug == $c->id) {
                    $debuginfo .= "Course Remove (exclude {$c->id} after display in coursearea $key\n";
                }
            }
        }

        if (!empty($areacourses)) {

            $courseareatpl = new StdClass();

            if ($i != 0 && ($i % 3 == 0)) {
                $courseareatpl->coljump = true;
            }
            $courseareatpl->colwidth = $colwidth;
            $courseareatpl->catname = $mastercategory->name;
            $courseareatpl->i = $reali;

            // Solve a performance issue for people having wide access to courses.
            $courseareatpl->coursesbycats = $renderer->courses_by_cats($areacourses, $options, 'courseareas_'.$i);

            $template->isaccordion = !empty($config->courselistaccordion);
            $template->courseareas[] = $courseareatpl;

            $reali++;
        }
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/courseareas_module', $template);
}

/**
 * Prints the specific courses area as a 3 column link list. Courses not enrolled will not appear here.
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
 // TRANSCODED
function local_my_print_course_areas2(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $DB, $PAGE, $USER;

    $config = get_config('local_my');
    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';

    if (empty($config->courseareas2)) {
        // Performance quick trap if no areas defined at all.
        return;
    }

    // Get all courses i am in.
    $allcourses = enrol_get_my_courses('id, shortname, fullname');

    $renderer = $PAGE->get_renderer('local_my');

    $options = array();
    $options['withcats'] = $config->printcategories;
    $options['gaugewidth'] = $config->progressgaugewidth;
    $options['gaugeheight'] = $config->progressgaugeheight;
    $options['gaugetype'] = $config->progressgaugetype;

    // Ensure we have last access.
    foreach ($allcourses as $id => $c) {
        $params = array('userid' => $USER->id, 'courseid' => $id);
        $allcourses[$id]->lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', $params);
    }

    $template = new StdClass;

    $reali = 1;
    for ($i = 0; $i < $config->courseareas2; $i++) {

        $key = 'coursearea2_'.$i;

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

        list($view, $isstudent, $isteacher, $iscoursemanager) = local_my_resolve_view();

        foreach ($allcourses as $c) {

            $context = context_course::instance($c->id);
            // Treat site admins as standard users.
            // $editing = has_capability('moodle/course:manageactivities', $context, $USER, false);
            $hasteachingactivity = has_capability('local/my:isteacher', $context, $USER, false);
            $hasmanageractivity = has_capability('local/my:iscoursemanager', $context, $USER, false);

            // Filter out non editing.
            if ($view == 'asteacher') {
                if (!$hasteachingactivity) {
                    continue;
                }
            } else if ($view == 'ascoursemanager') {
                if (!$hasmanageractivity) {
                    continue;
                }
            } else {
                if ($hasteachingactivity || $hasmanageractivity) {
                    continue;
                }
            }

            if (in_array($c->category, $retainedcategories)) {
                if ($debug == 1 || $debug == $c->id) {
                    $debuginfo .= "Course Add ({$c->id} in course area $key\n";
                }
                $areacourses[$c->id] = $c;
                $excludedcourses[] = $c->id;
                if ($debug == 1 || $debug == $c->id) {
                    $debuginfo .= "Course Remove (exclude $c->id after display in coursearea $key\n";
                }
            }
        }

        $colwidth = false;
        if ($config->courseareas2 % 3 == 0) {
            $colwidth = 33;
        }

        if (!$colwidth) {
            if ($config->courseareas2 % 2 == 0) {
                $colwidth = 50;
            }
        }

        if (!$colwidth) {
            switch ($config->courseareas2) {
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

            $courseareatpl = new StdClass();

            if ($reali % 3 == 0) {
                $courseareatpl->coljump = true;
            }
            $courseareatpl->colwidth = $colwidth;
            $courseareatpl->catname = $mastercategory->name;
            $courseareatpl->i = $reali;

            // Solve a performance issue for people having wide access to courses.
            // $courseareatpl->coursesbycats = $renderer->courses_by_cats($areacourses, $options, 'courseareas2_'.$i);
            $options['area'] = 'courseareas2_'.$i;
            $courseareatpl->categories = local_my_export_courses_cats_for_template($areacourses, $options);

            $template->courseareas[] = $courseareatpl;
            $template->isaccordion = !empty($config->courselistaccordion);

            $reali++;
        }
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
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

    $config = get_config('local_my');
    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';

    if (!$config->courseareas) {
        // Performance quick trap.
        return;
    }

    $mycourses = enrol_get_my_courses('id,shortname,fullname');
    $availablecourses = local_get_enrollable_courses();

    $renderer = $PAGE->get_renderer('local_my');

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
            if ($debug == 1 || $debug == $cid) {
                $debuginfo .= "Course Remove : (reject enrolled as excluded $cid )\n";
            }
            if (array_key_exists($cid, $mycourses)) {
                unset($mycourses[$cid]);
            }
            if (array_key_exists($cid, $availablecourses)) {
                if ($debug == 1 || $debug == $cid) {
                    $debuginfo .= "course Remove : (reject available as excluded $cid)\n";
                }
                unset($availablecourses[$cid]);
            }
        }
    }

    foreach ($mycourses as $cid => $c) {
        // TODO Add logger selection.
        $mycourses[$cid]->lastaccess = $DB->get_field('logstore_standard_log', 'max(timecreated)', array('courseid' => $cid));
    }

    $template = new StdClass();

    $options['noheading'] = 1;
    $options['nooverview'] = 1;
    $options['withdescription'] = 0;
    $options['withcats'] = $config->printcategories;
    $options['withcats'] = 0; // Which one ???
    $options['gaugewidth'] = $config->progressgaugewidth;
    $options['gaugeheight'] = $config->progressgaugeheight;
    $options['gaugetype'] = $config->progressgaugetype;

    $reali = 1;
    for ($i = 0; $i < $config->courseareas; $i++) {

        $coursearea = 'coursearea'.$i;
        $mastercategory = $DB->get_record('course_categories', array('id' => $config->$coursearea));
        if (!$mastercategory) {
            if (debugging()) {
                $template->courseareas[] = $OUTPUT->notification('Course master area is not valid '.$coursearea);
            }
            continue;
        }

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
            $courseareatpl = new StdClass();
            if ($reali % 3 == 0) {
                $courseareatpl->coljump = true;
            }
            $courseareatpl->catname = format_string($mastercategory->name);
            $courseareatpl->i = $reali;

            if (empty($options['nooverview'])) {
                // Solve a performance issue for people having wide access to courses.
                $courseareatpl->coursesbycats = local_print_courses_by_cats($myareacourses, $options);
            } else {
                // Aggregate my courses with the available and print in one unique list.
                $availableareacourses = array_merge($myareacourses, $availableareacourses);
            }
            if (!empty($availableareacourses)) {
                $courseareatpl->availables = local_my_export_courses_cats_for_template($availableareacourses, $options);
            }

            $template->courseareas[] = $courseareatpl;
            $template->isaccordion = !empty($config->courselistaccordion);

            $reali++;
        }
    }

    if ($debug) {
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/courseareas_module', $template);
}

/**
 * Prints the available (enrollable) courses as simple link entries
 * @param arrayref &$excludedcourses and array of courses that need NOT be displayed here.
 * @param arrayref &$courseareacourses courses reserved for display in further course area boxes.
 */
 // TRANSCODED
function local_my_print_available_courses(&$excludedcourses, &$courseareacourses) {
    global $OUTPUT, $PAGE;

    $debug = optional_param('showresolve', false, PARAM_INT);
    $debuginfo = '';
    $renderer = $PAGE->get_renderer('local_my');
    $config = get_config('local_my');
    $template = new StdClass();

    $availablecourses = local_my_get_availablecourses_courses($debuginfo, $excludedcourses);

    // Default gauge settings.
    $options = [
        'withteachersignals' => true,
        'gaugetype' => $config->progressgaugetype,
        'gaugewidth' => $config->progressgaugewidth,
        'gaugeheight' => $config->progressgaugeheight
    ];

    if (!empty($config->effect_opacity)) {
        $template->withopacityeffect = 'with-opacity-effect';
    }

    if (!empty($config->effect_halo)) {
        $template->withhaloeffect = 'with-halo-effect';
    }

    $template->area = 'available_courses';
    $template->modulename = get_string('availablecourses', 'local_my');

    if (empty($availablecourses)) {
        $template->hascourses = false;
        $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }
    if (empty($availablecourses)) {
        // In case we cannot create and all courses where gone elsewhere.
        return '';
    }

    $template->hascourses = true;
    $template->required = $required;

    local_my_resolve_viewtype($template, count($availablecourses));

    if (!empty($template->asflatlist) || !empty($template->asgrid)) {

        if (!empty($template->asflatlist)) {
            $options['gaugetype'] = 'sektor';
            $options['gaugewidth'] = '20';
            $options['gaugeheight'] = '20';
        } else {
            $options['gaugetype'] = $config-progressgaugetype;
            $options['gaugewidth'] = $config-progressgaugewidth;
            $options['gaugeheight'] = $config-progressgaugeheight;
        }

        // Get a simple, one level list.
        foreach ($availablecourses as $cid => $c) {
            $coursetpl = local_my_export_course_for_template($c, $options);
            $template->courses[] = $coursetpl;
        }

    } else {
        // as list.
        $template->isaccordion = !empty($config->courselistaccordion);
        $options['gaugetype'] = 'sektor';
        $options['gaugewidth'] = '20';
        $options['gaugeheight'] = '20';
        $result = local_my_export_courses_cats_for_template($availablecourses, $options);
        $template->categories = $result->categories;
        $template->catidlist = $result->catidlist;
    }


    $debuginfo .= local_my_exclude_post_display($availablecourses, $excludedcourses, 'available');

    if ($debug) {
        // Update debug info if necessary.
        $template->debuginfo = $debuginfo;
    }

    return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
}

/**
 * Prints the news forum as a list of full deployed discussions.
 */
 // TRANSCODED
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

        $template = new StdClass();

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
 // TRANSCODED
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

        $template = new StdClass();

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
 // TRANSCODED
function local_my_print_latestnews_simple() {
    global $PAGE, $SITE, $CFG, $OUTPUT, $DB, $SESSION;

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot .'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        $renderer = $PAGE->get_renderer('local_my');

        $template = new StdClass();
        $template->forumname = format_string($newsforum->name);
        $template->simple = true;

        $newsdiscussions = $DB->get_records('forum_discussions', array('forum' => $newsforum->id), 'timemodified DESC');
        foreach ($newsdiscussions as $news) {
            $discussiontpl = new StdClass();
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
 // TRANSCODED
function local_my_print_static($index) {
    global $CFG, $DB, $USER, $OUTPUT;

    if (!file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
        return $OUTPUT->notification(get_string('nostaticguitexts', 'local_my', 'static'));
    }

    $context = context_system::instance();
    $template = new StdClass();

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
            $e = new StdClass();
            $e->field = $field->name;
            $e->value = $profileexpectedvalue;
            $template->adminviewstr = get_string('adminview', 'local_my', $e);
            $template->isadminview = true;
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
        $profilevalue = str_replace(' ', '-', $profilevalue);
        $profilevalue = str_replace('_', '-', $profilevalue);
        $profilevalue = preg_replace("/[^0-9a-zA-Z-]/", '', $profilevalue);

        // This is a global match catching all values.
        if (has_capability('moodle/site:config', $context)) {

            $template->isadminview = true;

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

                    $modaltpl = new StdClass();
                    // Reformat key for token integrity.
                    if (is_object($modality)) {
                        $modality = core_text::strtolower($modality->data);
                    } else {
                        $modality = core_text::strtolower($modality);
                    }
                    $unfilteredmodality = trim($modality);
                    $modality = str_replace(' ', '-', $unfilteredmodality);
                    $modality = str_replace('_', '-', $modality);
                    $modality = preg_replace("/[^0-9a-zA-Z-]/", '', $modality);

                    $modaltpl->modalindex = $index.'-'.$modality;
                    $a = new StdClass;
                    $a->profile = $field->shortname;
                    $a->data = $modality;
                    $modaltpl->contentforstr = '<span class="shadow">('.get_string('contentfor', 'local_my', $a).')</span>';
                    $return = new moodle_url('/my/index.php');
                    $modaltpl->statictext = local_print_static_text('custommystaticarea-'.$modaltpl->modalindex, $return, '', true);
                    $modaltpl->visibilityclass = $visibilityclass;
                    $template->modalities[] = $modaltpl;
                    $visibilityclass = 'local-my-hide';

                    $modoptions[$modality] = $unfilteredmodality;
                }
                $template->hasmodalities = count($template->modalities);

                // Choose first as active.
                $attrs = array('id' => 'local-my-static-select-'.$index, 'class' => 'local-my-modality-chooser');
                $template->modalitiesselect = html_writer::select($modoptions, 'modalities', array_keys($modoptions)[0], null, $attrs);

            }
        }

        // Normal user, one sees his own.
        if (!empty($profilevalue)) {
            $modaltpl = new StdClass();
            $modaltpl->modalindex = $index.'-'.$profilevalue;

            $return = new moodle_url('/my/index.php');
            $modaltpl->statictext = local_print_static_text('custommystaticarea-'.$modaltpl->modalindex, $return, '', true);
            $template->modalities[] = $modaltpl;
        }
    } else if (is_numeric($index)) {
        // Simple indexed.

        $template = new StdClass();
        $template->index = $index;

        $return = new moodle_url('/my/index.php');
        $template->statictext = local_print_static_text('custommystaticarea-'.$template->index, $return, '', true);
    }

    return $OUTPUT->render_from_template('local_my/static_module', $template);
}

/**
 * Prints a widget with information about me.
 */
 // TRANSCODED
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
 // TRANSCODED
function local_my_print_fullme() {
    global $OUTPUT, $USER, $CFG;

    $context = context_system::instance();
    $template = new StdClass;

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
 // TRANSCODED
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
 * Prints a github like heat activity map on passed six months
 * @param int $userid the concerned userid
 */
 // TRANSCODED
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
 // TRANSCODED
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
 // TRANSCODED
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

// TRANSCODED
function local_my_print_course_search() {
    global $PAGE, $OUTPUT;

    $renderer = $PAGE->get_renderer('course');

    $str = '';

    $search = optional_param('search', '', PARAM_TEXT);

    $str .= $OUTPUT->box_start('my-modules course-search');

    $str .= $OUTPUT->box_start('box block');

    $str .= $OUTPUT->box_start('header');
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_start('content');
    $str .= $renderer->course_search_form($search, 'plain');
    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_end();

    $str .= $OUTPUT->box_end();

    return $str;
}

// TRANSCODED
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

// Let integrators add additional non generic modules.
if (file_exists($CFG->dirroot.'/local/my/local_modules.php')) {
    require_once($CFG->dirroot.'/local/my/local_modules.php');
}