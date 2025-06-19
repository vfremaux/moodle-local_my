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

// Overrides the customisation if not enabled and return back to standard behaviour

require_once($CFG->dirroot.'/my/lib.php');
require_once($CFG->dirroot.'/local/my/lib.php');
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
require_once($CFG->dirroot.'/local/my/classes/modules/my_courses.class.php');
require_once($CFG->dirroot.'/local/my/classes/modules/my_authored_courses.class.php');
require_once($CFG->dirroot.'/local/my/classes/modules/my_managed_courses.class.php');

use \local_my\module\module;

local_vflibs_require_jqplot_libs();

// TODO Add sesskey check to edit.
$edit = optional_param('edit', null, PARAM_BOOL);    // Turn editing on and off.
$showresolve = optional_param('showresolve', null, PARAM_INT);    // Turn check of how courses are dispatched.

// Security.

require_login();

$strmymoodle = get_string('myhome');

if (isguestuser()) {
    // Force them to see system default, no editing allowed.
    $userid = null;
    $USER->editing = $edit = 0;  // Just in case.
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('mydashboard');
    $PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // Unlikely :).
    $header = "$SITE->shortname: $strmymoodle (GUEST)";

} else {
    // We are trying to view or edit our own My Moodle page.
    $userid = $USER->id;  // Owner of the page.
    $context = context_user::instance($USER->id);
    $PAGE->set_context($context);
    $PAGE->set_pagelayout('mydashboard');
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = "$SITE->shortname: $strmymoodle";
}

$PAGE->add_body_class('limitedwidth'); // M4
$PAGE->add_body_class('customscripted');
$PAGE->requires->js('/local/my/js/sektor/sektor.js');
$PAGE->requires->css('/local/my/css/slick.css');

module::static_init();
if (empty(module::get_config('enable'))) {
    return -1;
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

$PAGE->requires->skip_link_to('localmymaincontent', get_string('tocontent', 'access'));

if (get_home_page() != HOMEPAGE_MY) {
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_MY);
    } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
        $linkurl = new moodle_url('/my/', array('setdefaulthome' => true));
        $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'), $linkurl, navigation_node::TYPE_SETTING);
    }
}

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

    // Add button for editing page (new 4.0)
    $params = array('edit' => !$edit);

    $resetbutton = '';
    $resetstring = get_string('resetpage', 'my');
    $reseturl = new moodle_url("$CFG->wwwroot/my/index.php", array('edit' => 1, 'reset' => 1));

    if (!$currentpage->userid) {
        // Viewing a system page -- let the user customise it.
        $editstring = get_string('updatemymoodleon');
        $params['edit'] = 1;
    } else if (empty($edit)) {
        $editstring = get_string('updatemymoodleon');
    } else {
        $editstring = get_string('updatemymoodleoff');
        $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
    }

    $url = new moodle_url("$CFG->wwwroot/my/index.php", $params);
    $button = '';
    if (!$PAGE->theme->haseditswitch) {
        $button = $OUTPUT->single_button($url, $editstring);
    }
    $PAGE->set_button($resetbutton . $button);

} else {
    $USER->editing = $edit = 0;
}

// HACK WARNING!  This loads up all this page's blocks in the system context.
if ($currentpage->userid == 0) {
    $CFG->blockmanagerclass = 'my_syspage_block_manager';
}

// Get exclusions startup from config.

// Get user status.
// TODO : change dynamically wether using teacher_courses or authored_courses in settings.
list($view, $isstudent, $isteacher, $iscoursemanager, $isadmin) = module::resolve_view();

$renderer = module::get_renderer();
$tabs = $renderer->tabs($view, $isstudent, $isteacher, $iscoursemanager, $isadmin);

module::fetch_modules($view);
// debug_trace("Processing exclusions");
module::pre_process_exclusions($view);

echo $OUTPUT->header();

if (core_userfeedback::should_display_reminder()) {
    core_userfeedback::print_reminder_block();
}

echo module::render_my_caption();

echo $tabs;

// Render dahsboard.
// debug_trace("Rendering all dashboard");
echo module::render_dashboard();

// The main overview in the middle of the page.

// Ask for rendering js sektor code in main page.
$PAGE->requires->js_amd_inline($renderer->render_js_code(false));

echo $OUTPUT->footer();

// Trigger dashboard has been viewed event.
$eventparams = array('context' => $context);
$event = \core\event\dashboard_viewed::create($eventparams);
$event->trigger();

die;