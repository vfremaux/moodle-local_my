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

defined('MOODLE_INTERNAL') || die();

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

// This is a customscript include
// require_once(dirname(__FILE__) . '/../config.php');

// overrides the customisation if not enabled and return back to standard behaviour....
$config = get_config('local_my');

if (empty($config->enable)) {
    return;
}

require_once($CFG->dirroot.'/my/lib.php');
require_once($CFG->dirroot.'/local/my/lib.php');

// TODO Add sesskey check to edit
$edit   = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off

// Security.

require_login();

if (!isset($config->maxoverviewedlistsize)) {
    set_config('maxoverviewedlistsize', MAX_COURSE_OVERVIEWED_LIST, 'local_my');
}

$strmymoodle = get_string('myhome');

if (isguestuser()) {  // Force them to see system default, no editing allowed
    $userid = null;
    $USER->editing = $edit = 0;  // Just in case
    $context = context_system::instance();
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
    $header = "$SITE->shortname: $strmymoodle (GUEST)";

} else {        // We are trying to view or edit our own My Moodle page
    $userid = $USER->id;  // Owner of the page
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = "$SITE->shortname: $strmymoodle";
}

// Get the My Moodle page info.  Should always return something unless the database is broken.
if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
    print_error('mymoodlesetup');
}

if (!$currentpage->userid) {
    $context = context_system::instance();  // So we even see non-sticky blocks
}

// Start setting up the page.
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($header);
$PAGE->set_heading($header);

$PAGE->requires->jquery_plugin('jqwidgets-core', 'local_vflibs');
$PAGE->requires->jquery_plugin('jqwidgets-bargauge', 'local_vflibs');
$PAGE->requires->jquery_plugin('jqwidgets-progressbar', 'local_vflibs');

if (get_home_page() != HOMEPAGE_MY) {
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_MY);
    } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
        $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'), new moodle_url('/my/', array('setdefaulthome'=>true)), navigation_node::TYPE_SETTING);
    }
}

// Toggle the editing state and switches
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) {             // Editing state was specified
        $USER->editing = $edit;       // Change editing state
        if (!$currentpage->userid && $edit) {
            // If we are viewing a system page as ordinary user, and the user turns
            // editing on, copy the system pages as new user pages, and get the
            // new page record
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
        }
    } else {                          // Editing state is in session
        if ($currentpage->userid) {   // It's a page we can edit, so load from session
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {                      // It's a system page and they are not allowed to edit system pages
            $USER->editing = $edit = 0;          // Disable editing completely, just to be safe
        }
    }

    // Add button for editing page
    $params = array('edit' => !$edit);

    if (!$currentpage->userid) {
        // viewing a system page -- let the user customise it
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

// HACK WARNING!  This loads up all this page's blocks in the system context
if ($currentpage->userid == 0) {
    $CFG->blockmanagerclass = 'my_syspage_block_manager';
}

// Get and clean modules names.

$my_modules = array();
$my_left_modules = array();
if ($config->modules) {
    $modules = preg_split("/[\\n,]|\\s+/", $config->modules);
    for ($i = 0 ; $i < count($modules) ; $i++) {
        $module = trim($modules[$i]);
        $modules[$i] = $module; // store it back into full modules list
        if (preg_match('/-L$/', $module)) {
            $my_left_modules[$i] = preg_replace('/-L$/', '', $module);
        } else {
            // in case it has been explicitely right-located (default);
            $my_modules[$i] = preg_replace('/-R$/', '', $module);
        }
    }
}

echo $OUTPUT->header();

//  echo $OUTPUT->blocks_for_region('content');
echo '<div id="my-content">';

if (in_array('my_caption', $my_modules)) {
    local_print_static_text('my_caption_static_text', $CFG->wwwroot.'/my/index.php');
}

$fooarray = null;
$courseareacourses = $excludedcourses = array();
if ((in_array('course_areas', $modules) || in_array('course_areas_and_availables', $modules)) && $config->courseareas > 0) {
    $excludedcourses = $courseareacourses = local_prefetch_course_areas($fooarray);
}

echo '<table id="mydashboard" width="100%" cellpadding="10"><tr valign="top">';

if (in_array('left_edition_column', $my_modules)) {
    $colwidth = 50;
    echo "<td id=\"my-dashboard-left\" width=\"{$colwidth}%\">";
    if (function_exists('local_print_static_text')) {
        // In case the local_staticguitexts is coming with.
        local_print_static_text('my_caption_left_column_static_text', $CFG->wwwroot.'/my/index.php');
    }

    if (!empty($my_left_modules)) {
        foreach ($my_left_modules as $m) {
            $m = trim($m);
            if (empty($m) || preg_match('/^\s+$/', $m)) continue; // blank lines
            if (preg_match('/^[!_*#]/', $m)) continue; // ignore some modules
            if ($m == 'my_caption' || $m == 'left_edition_column') continue; // special cases
    
            // special case : print statics can be freely indexed
            if (preg_match('/static(\d+)$/', $m, $matches)) {
                $fname = 'local_my_print_static';
                echo $fname($matches[1]);
                continue;
            }

            $fname = 'local_my_print_'.$m;
            if (!function_exists($fname)) {
                echo get_string('unknownmodule', 'local_my', $fname).'<br/>';
            } else {
                echo $fname($excludedcourses, $courseareacourses);
            }
        }
    }

    echo '</td>';
} else {
    $colwidth = 100;
}

echo "<td id=\"my-dashboard-right\" width=\"{$colwidth}%\">";

// The main overview in the middle of the page

foreach ($my_modules as $m) {
    $m = trim($m);
    if (empty($m) || preg_match('/^\s+$/', $m)) continue; // blank lines
    if (preg_match('/^[!_*#]/', $m)) continue; // ignore some modules
    if ($m == 'my_caption' || $m == 'left_edition_column') continue; // special cases

    // special case : print statics can be freely indexed
    if (preg_match('/static(\d+)$/', $m, $matches)) {
        $fname = 'local_my_print_static';
        echo $fname($matches[1]);
        continue;
    }

    $fname = 'local_my_print_'.$m;
    if (!function_exists($fname)) {
        echo get_string('unknownmodule', 'local_my', $fname).'<br/>';
    } else {
        echo $fname($excludedcourses, $courseareacourses);
    }
}

echo '</td></tr></table>';
echo '</div>';
echo '</td>';

echo $OUTPUT->footer();

die;
