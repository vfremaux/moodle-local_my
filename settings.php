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

require_once($CFG->dirroot.'/lib/coursecatlib.php');

$config = get_config('local_my');

if ($hassiteconfig) { 
    // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_my', get_string('pluginname', 'local_my'));
    $ADMIN->add('localplugins', $settings);

    $yesnooptions[0] = get_string('no');
    $yesnooptions[1] = get_string('yes');

    $settings->add(new admin_setting_configcheckbox('local_my/enable', get_string('localmyenable', 'local_my'), get_string('localmyenabledesc', 'local_my'), 0));

    $settings->add(new admin_setting_configcheckbox('local_my/force', get_string('localmyforce', 'local_my'), get_string('localmyforcedesc', 'local_my'), 0));

    $settings->add(new admin_setting_configselect('local_my/skipmymetas', get_string('localskipmymetas', 'local_my'), get_string('localskipmymetasdesc', 'local_my'), 0, $yesnooptions, PARAM_BOOL));

    $defaultmodules = "me\nmy_heatmap-L\nleft_edition_column\nmy_courses\nauthored_courses\ncourse_areas\navailable_courses\nlatestnews_simple";
    if (!isset($config->modules)) {
        set_config('localmymodules', $defaultmodules);
    }
    $settings->add(new admin_setting_configtextarea('local_my/modules', get_string('localmymodules', 'local_my'), get_string('localmymodulesdesc', 'local_my'), $defaultmodules));

    $settings->add(new admin_setting_heading('header2', get_string('courseareasettings', 'local_my'), ''));

    $options = array();
    $options[0] = get_string('nocourseareas', 'local_my');
    for ($i = 1 ; $i < 10 ; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('local_my/courseareas', get_string('localmycourseareas', 'local_my'), get_string('localmycourseareasdesc', 'local_my'), 0, $options, PARAM_INT));

    global $SITE;

    $categoryoptions = coursecat::make_categories_list();
    $categoryoptions[0] = $SITE->fullname;
    asort($categoryoptions);
    for ($i = 0; $i < @$config->courseareas; $i++) {
        $settings->add(new admin_setting_configselect('local_my/coursearea'.$i, get_string('localmycoursearea', 'local_my').' '.$i, '', 0, $categoryoptions, PARAM_INT));
    }

    $settings->add(new admin_setting_heading('header3', get_string('categorysettings', 'local_my'), ''));

    $settings->add(new admin_setting_configselect('local_my/printcategories', get_string('localmyprintcategories', 'local_my'), get_string('localmyprintcategoriesdesc', 'local_my'), 0, $yesnooptions, PARAM_BOOL));

    $overviewedoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20);
    $settings->add(new admin_setting_configselect('local_my/maxoverviewedlistsize', get_string('localmymaxoverviewedlistsize', 'local_my'), get_string('localmymaxoverviewedlistsizedesc', 'local_my'), 10, $overviewedoptions, PARAM_INT));

    $availableoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50);
    $settings->add(new admin_setting_configselect('local_my/maxavailablelistsize', get_string('localmymaxavailablelistsize', 'local_my'), get_string('localmymaxavailablelistsizedesc', 'local_my'), 30, $availableoptions, PARAM_INT));

    $uncategorizedoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20, 50 => 50, 100 => 100);
    $settings->add(new admin_setting_configselect('local_my/maxuncategorizedlistsize', get_string('localmymaxuncategorizedlistsize', 'local_my'), get_string('localmymaxuncategorizedlistsizedesc', 'local_my'), 10, $uncategorizedoptions, PARAM_INT));

    $settings->add(new admin_setting_heading('header4', get_string('heatmapsettings', 'local_my'), ''));

    $heatmapoptions = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 8 => 8, 10 => 10, 12 => 12);
    $settings->add(new admin_setting_configselect('local_my/heatmaprange', get_string('localmyheatmaprange', 'local_my'), get_string('localmyheatmaprangedesc', 'local_my'), 6, $heatmapoptions, PARAM_INT));
}

