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
 * @package     local_my
 * @copyright   2016 onwards Valery Fremaux <http://docs.activeprolearn.com/en>
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);

require('../../../config.php');

require_once($CFG->dirroot.'/local/my/lib.php');
require_once($CFG->dirroot.'/local/my/classes/modules/module.class.php');

use \local_my\module\module;

$action = optional_param('what', '', PARAM_ALPHA);
$courseid = optional_param('courseid', '', PARAM_INT);

require_login();
$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/my/ajax/service.php', ['what' => $action,'courseid' => $courseid]));

if (!empty($action)) {
    if ($action == 'addtofavorites') {
        local_my_add_to_favorites($courseid);
    }

    if ($action == 'removefromfavorites') {
        local_my_remove_from_favorites($courseid);
    }

    if ($action == 'getfavorites') {
        $view = required_param($view, PARAM_TEXT);
        local_my_render_favorites();
    }

    if ($action == 'getcourses') {

        $view = required_param('view', PARAM_TEXT);

        module::static_init();
        module::resolve_view();
        module::fetch_modules($view);
        module::pre_process_exclusions($view);

        // Get course list for a widget.
        $renderer = $PAGE->get_renderer('local_my');

        $widget = required_param('widget', PARAM_TEXT);
        $uid = required_param('uid', PARAM_INT);
        echo $renderer->render_ajax_widget($uid, $widget);
        echo $renderer->render_js_code(true);
    }
}

