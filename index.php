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
 * My Moodle -- a user's personal dashboard
 *
 * - each user can currently have their own page (cloned from system and then customised)
 * - only the user can see their own dashboard
 * - users can add any blocks they want
 * - the administrators can define a default site dashboard for users who have
 *   not created their own dashboard
 *
 * This script implements the user's view of the dashboard, and allows editing
 * of the dashboard.
 *
 * @package    local_my
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This is a customscript include.
defined('MOODLE_INTERNAL') || die();

// Overrides the customisation if not enabled and return back to standard behaviour....
$config = get_config('local_my');

if (empty($config->enable)) {
    return -1;
}

require_once($CFG->dirroot.'/my/lib.php');
require_once($CFG->dirroot.'/local/my/lib.php');
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');

local_vflibs_require_jqplot_libs();

// TODO Add sesskey check to edit.
$edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.

// Security.

require_login();

$strmymoodle = get_string('myhome');

if (isguestuser()) {
    // Force them to see system default, no editing allowed.
    $userid = null;
    $USER->editing = $edit = 0;  // Just in case.
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // Unlikely :).
    $header = "$SITE->shortname: $strmymoodle (GUEST)";

} else {
    // We are trying to view or edit our own My Moodle page.
    $userid = $USER->id;  // Owner of the page.
    $context = context_user::instance($USER->id);
    $PAGE->set_context($context);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = "$SITE->shortname: $strmymoodle";
}

// Get the My Moodle page info.  Should always return something unless the database is broken.
if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
    print_error('mymoodlesetup');
}

if (!$currentpage->userid) {
    $context = context_system::instance();  // So we even see non-sticky blocks.
}

// Start setting up the page.
$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($header);
$PAGE->set_heading($header);

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('jqwidgets-core', 'local_vflibs');
$PAGE->requires->jquery_plugin('jqwidgets-bargauge', 'local_vflibs');
$PAGE->requires->jquery_plugin('jqwidgets-progressbar', 'local_vflibs');
$PAGE->requires->js_call_amd('local_my/local_my', 'init');
if (!empty($config->slick)) {
    $PAGE->requires->js_call_amd('local_my/slick', 'init');
    $PAGE->requires->js_call_amd('local_my/slickinit', 'init');
}
$PAGE->requires->css('/local/my/css/slick.css');

if (get_home_page() != HOMEPAGE_MY) {
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_MY);
    } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
        $linkurl = new moodle_url('/my/', array('setdefaulthome' => true));
        $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'), $linkurl, navigation_node::TYPE_SETTING);
    }
}

$renderer = $PAGE->get_renderer('local_my');

// Toggle the editing state and switches.
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) {
        // Editing state was specified.
        $USER->editing = $edit;       // Change editing state.
        if (!$currentpage->userid && $edit) {
            /*
             * If we are viewing a system page as ordinary user, and the user turns
             * editing on, copy the system pages as new user pages, and get the
             * new page record
             */
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
        }
    } else {
        // Editing state is in session.
        if ($currentpage->userid) {
            // It's a page we can edit, so load from session.
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {
            // It's a system page and they are not allowed to edit system pages.
            $USER->editing = $edit = 0; // Disable editing completely, just to be safe.
        }
    }

    // Add button for editing page.
    $params = array('edit' => !$edit);

    if (!$currentpage->userid) {
        // Viewing a system page -- let the user customise it.
        $editstring = get_string('updatemymoodleon');
        $params['edit'] = 1;
    } else if (empty($edit)) {
        $editstring = get_string('updatemymoodleon');
    } else {
        $editstring = get_string('updatemymoodleoff');
    }

    $url = new moodle_url('/my/index.php', $params);
    $button = $OUTPUT->single_button($url, $editstring);
    $PAGE->set_button($button);

} else {
    $USER->editing = $edit = 0;
}

// HACK WARNING!  This loads up all this page's blocks in the system context.
if ($currentpage->userid == 0) {
    $CFG->blockmanagerclass = 'my_syspage_block_manager';
}

// Get exclusions startup from config.
$excludedcourses = explode(',', @$config->excludedcourses);

// Get user status.
// TODO : change dynamically wether using teacher_courses or authored_courses in settings.
$teachercap = 'local/my:isteacher';
$authorcap = 'local/my:isauthor';
$coursemanagercap = 'local/my:iscoursemanager';
$isteacher = local_my_has_capability_somewhere($teachercap) ||
        local_my_has_capability_somewhere($authorcap, true, true, false, CONTEXT_COURSECAT);
$iscoursemanager = local_my_has_capability_somewhere($coursemanagercap);

// Get and clean modules names.

echo $OUTPUT->header();

$view = optional_param('view', '', PARAM_TEXT);
if (empty($view)) {
    if ($isteacher) {
        // Defaults for teachers.
        $view = 'asteacher';
    }
    if ($iscoursemanager) {
        // Defaults for coursemanagers.
        $view = 'ascoursemanager';
    }
}

// We need prefetch tabs as it may resolve view.
$tabs = $renderer->tabs($view, $isteacher, $iscoursemanager);

list($modules, $mymodules, $myleftmodules) = local_my_fetch_modules($view);

if (in_array('my_caption', $mymodules)) {
    if (file_exists($CFG->dirroot.'/local/staticguitexts/lib.php')) {
        include_once($CFG->dirroot.'/local/staticguitexts/lib.php');
        local_print_static_text('my_caption_static_text', $CFG->wwwroot.'/my/index.php');
    } else {
        echo $OUTPUT->notification(get_string('nostaticguitexts', 'local_my', 'my_caption'));
    }
}

echo $tabs;
echo $OUTPUT->box_start('', 'my-content');

$fooarray = null;
$courseareacourses = array();

// Calculate course areas content for exclusions.
if ((in_array('course_areas', $modules) ||
        in_array('course_areas_and_availables', $modules)) &&
                @$config->courseareas > 0) {
    $courseareacourses = local_prefetch_course_areas($fooarray);

    $courseareaskeys = array_keys($courseareacourses);
    local_my_scalar_array_merge($excludedcourses, $courseareaskeys);
}

if ($view == 'asstudent' && $isteacher) {
    // If i am teacher and viewing the student tab, prefech teacher courses to exclude them.
    $prefetchcourses = local_get_my_authoring_courses('id', $teachercap);
    $prefetchkeys = array_keys($prefetchcourses);
    local_my_scalar_array_merge($excludedcourses, $prefetchkeys);
}

// Render dahsboard.

echo $OUTPUT->box_start('container-fluid', 'mydashboard'); // Table.
echo $OUTPUT->box_start('row-fluid', 'mydashboard-row'); // Row.

if (in_array('left_edition_column', $mymodules)) {
    $spanclass = 'span6 col-md-6 col-xs-12';
    echo $OUTPUT->box_start('span6 col-md-6 col-xs-12', 'my-dashboard-left');

    if (function_exists('local_print_static_text')) {
        // In case the local_staticguitexts is coming with.
        local_print_static_text('my_caption_left_column_static_text', $CFG->wwwroot.'/my/index.php');
    }

    if (!empty($myleftmodules)) {
        foreach ($myleftmodules as $m) {
            local_my_render_module($m, $excludedcourses, $courseareacourses);
        }
    }

    echo $OUTPUT->box_end();
} else {
    $spanclass = 'span12 col-xs-12';
}

// The main overview in the middle of the page.

echo $OUTPUT->box_start($spanclass, 'my-dashboard-right');
foreach ($mymodules as $m) {
    local_my_render_module($m, $excludedcourses, $courseareacourses);
}
echo $OUTPUT->box_end();

echo $OUTPUT->box_end();
echo $OUTPUT->box_end();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
die;