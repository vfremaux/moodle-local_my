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
 * @package    local_my
 * @category   local
 * @reauthor   Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/my/lib.php');

$localmyconfig = get_config('local_my');

/*
 * This hook redraws the my routing policy using local/my:overridemy switch and my force setting.
 */
if (get_home_page() != HOMEPAGE_SITE && !isguestuser($USER->id)) {
    // Redirect logged-in users to My Moodle overview if required.
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MY)) {
        if ($localmyconfig->force && !local_has_myoverride_somewhere()) {
            redirect(new moodle_url('/my'));
        }
        if (optional_param('redirect', 1, PARAM_BOOL) === 1) {
            redirect(new moodle_url('/my'));
        }
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_USER)) {
        $linkurl = new moodle_url('/', array('setdefaulthome' => true));
        $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'), $linkurl, navigation_node::TYPE_SETTING);
    }
}
if (isguestuser($USER->id)) {
    if ($localmyconfig->force && $CFG->allowguestmymoodle) {
        redirect(new moodle_url('/my'));
    }
}