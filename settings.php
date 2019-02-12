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
 */
defined('MOODLE_INTERNAL') || die();

// Settings default init.
if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    // Integration driven code.
    require_once($CFG->dirroot.'/local/adminsettings/lib.php');
    list($hasconfig, $hassiteconfig, $capability) = local_adminsettings_access();
} else {
    // Standard Moodle code.
    $capability = 'moodle/site:config';
    $hasconfig = $hassiteconfig = has_capability($capability, context_system::instance());
}

$config = get_config('local_my');

if ($hassiteconfig) {
    // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_my', get_string('pluginname', 'local_my'));
    $displaysettings = new admin_settingpage('local_my_fast', get_string('localmylayout', 'local_my'));
    $ADMIN->add('localplugins', $settings);
    if (!empty($config->enable)) {
        $ADMIN->add('appearance', $displaysettings);
    }

    $yesnooptions[0] = get_string('no');
    $yesnooptions[1] = get_string('yes');

    $key = 'local_my/enable';
    $label = get_string('localmyenable', 'local_my');
    $desc = get_string('localmyenable_desc', 'local_my');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

    $key = 'local_my/force';
    $label = get_string('localmyforce', 'local_my');
    $desc = get_string('localmyforce_desc', 'local_my');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

    $key = 'local_my/skipmymetas';
    $label = get_string('localskipmymetas', 'local_my');
    $desc = get_string('localskipmymetas_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions, PARAM_BOOL));
    $displaysettings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions, PARAM_BOOL));

    $key = 'local_my/excludedcourses';
    $label = get_string('localmyexcludedcourses', 'local_my');
    $desc = get_string('localmyexcludedcourses_desc', 'local_my');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, ''));
    $displaysettings->add(new admin_setting_configtextarea($key, $label, $desc, ''));

    $defaultmodules = "me\nadmin_stats\ncourse_search\nmy_network\nmy_heatmap";
    if (!isset($config->adminmodules)) {
        set_config('adminmodules', $defaultmodules, 'local_my');
    }
    $key = 'local_my/adminmodules';
    $label = get_string('localmyadminmodules', 'local_my');
    $desc = get_string('localmyadminmodules_desc', 'local_my');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));
    $displaysettings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));

    $defaultmodules = "my_caption\nme\ncourse_search\nmanaged_courses\n";
    $defaultmodules .= "latestnews_simple\nmy_heatmap";
    if (!isset($config->teachermodules)) {
        set_config('coursemanagermodules', $defaultmodules, 'local_my');
    }
    $key = 'local_my/coursemanagermodules';
    $label = get_string('localmycoursemanagermodules', 'local_my');
    $desc = get_string('localmycoursemanagermodules_desc', 'local_my');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));
    $displaysettings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));

<<<<<<< HEAD
    $defaultmodules = "my_caption\nme\ncourse_search\nauthored_courses\n";
=======
    $defaultmodules = "my_caption\nme\ncourse_search\nteacher_courses\n";
>>>>>>> MOODLE_36_STABLE
    $defaultmodules .= "latestnews_simple\nmy_heatmap";
    if (!isset($config->teachermodules)) {
        set_config('teachermodules', $defaultmodules, 'local_my');
    }
    $key = 'local_my/teachermodules';
    $label = get_string('localmyteachermodules', 'local_my');
    $desc = get_string('localmyteachermodules_desc', 'local_my');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));
    $displaysettings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));

    $defaultmodules = "my_caption\nme\nmy_courses\n";
    $defaultmodules .= "course_areas\navailable_courses\nlatestnews_simple\nmy_heatmap";
    if (!isset($config->modules)) {
        set_config('modules', $defaultmodules, 'local_my');
    }
    $key = 'local_my/modules';
    $label = get_string('localmymodules', 'local_my');
    $desc = get_string('localmymodules_desc', 'local_my');
    $settings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));
    $displaysettings->add(new admin_setting_configtextarea($key, $label, $desc, $defaultmodules));

    $settings->add(new admin_setting_heading('header2', get_string('courseareasettings', 'local_my'), ''));

    $options = array();
    $options[0] = get_string('nocourseareas', 'local_my');
    for ($i = 1; $i < 10; $i++) {
        $options[$i] = $i;
    }
    $key = 'local_my/courseareas';
    $label = get_string('localmycourseareas', 'local_my');
    $desc = get_string('localmycourseareas_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $options, PARAM_INT));
    $displaysettings->add(new admin_setting_configselect($key, $label, $desc, 0, $options, PARAM_INT));

    global $SITE;

    $categoryoptions = \core_course_category::make_categories_list();
    $categoryoptions[0] = $SITE->fullname;
    asort($categoryoptions);
    for ($i = 0; $i < @$config->courseareas; $i++) {
        $key = 'local_my/coursearea'.$i;
        $label = get_string('localmycoursearea', 'local_my').' A '.$i;
        $settings->add(new admin_setting_configselect($key, $label, '', 0, $categoryoptions, PARAM_INT));
        $displaysettings->add(new admin_setting_configselect($key, $label, '', 0, $categoryoptions, PARAM_INT));
    }

    $options = array();
    $options[0] = get_string('nocourseareas', 'local_my');
    for ($i = 1; $i < 10; $i++) {
        $options[$i] = $i;
    }
    $key = 'local_my/courseareas2';
    $label = get_string('localmycourseareas', 'local_my').' 2';
    $desc = get_string('localmycourseareas_desc', 'local_my').' 2';
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $options, PARAM_INT));
    $displaysettings->add(new admin_setting_configselect($key, $label, $desc, 0, $options, PARAM_INT));

<<<<<<< HEAD
    $categoryoptions = coursecat::make_categories_list();
=======
    $categoryoptions = \core_course_category::make_categories_list();
>>>>>>> MOODLE_36_STABLE
    $categoryoptions[0] = $SITE->fullname;
    asort($categoryoptions);
    for ($i = 0; $i < @$config->courseareas2; $i++) {
        $key = 'local_my/coursearea2_'.$i;
        $label = get_string('localmycoursearea', 'local_my'). ' 2 - A '.$i;
        $settings->add(new admin_setting_configselect($key, $label, '', 0, $categoryoptions, PARAM_INT));
        $displaysettings->add(new admin_setting_configselect($key, $label, '', 0, $categoryoptions, PARAM_INT));
    }

    $settings->add(new admin_setting_heading('header3', get_string('categorysettings', 'local_my'), ''));

    $key = 'local_my/printcategories';
    $label = get_string('localmyprintcategories', 'local_my');
    $desc = get_string('localmyprintcategories_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnooptions, PARAM_BOOL));

    $identifieroptions = ['' => get_string('none', 'local_my'),
                          'shortname' => get_string('shortname'),
                          'idnumber' => get_string('idnumber')];

    $key = 'local_my/showcourseidentifier';
    $label = get_string('localmyshowcourseidentifier', 'local_my');
    $desc = get_string('localmyshowcourseidentifier_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $identifieroptions, PARAM_TEXT));

    /*
    // Obsolete in 3.5
    $overviewedoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20);
    $key = 'local_my/maxoverviewedlistsize';
    $label = get_string('localmymaxoverviewedlistsize', 'local_my');
    $desc = get_string('localmymaxoverviewedlistsize_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 10, $overviewedoptions, PARAM_INT));
    */

    $availableoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50);
    $key = 'local_my/maxavailablelistsize';
    $label = get_string('localmymaxavailablelistsize', 'local_my');
    $desc = get_string('localmymaxavailablelistsize_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 30, $availableoptions, PARAM_INT));

    $uncategorizedoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20, 50 => 50, 100 => 100);
    $key = 'local_my/maxuncategorizedlistsize';
    $label = get_string('localmymaxuncategorizedlistsize', 'local_my');
    $desc = get_string('localmymaxuncategorizedlistsize_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 10, $uncategorizedoptions, PARAM_INT));

    $settings->add(new admin_setting_heading('header4', get_string('heatmapsettings', 'local_my'), ''));

    $heatmapoptions = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 8 => 8, 10 => 10, 12 => 12);
    $key = 'local_my/heatmaprange';
    $label = get_string('localmyheatmaprange', 'local_my');
    $desc = get_string('localmyheatmaprange_desc', 'local_my');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 6, $heatmapoptions, PARAM_INT));

    $key = 'local_my/slick';
    $label = get_string('localmyslick', 'local_my');
    $desc = get_string('localmyslick_desc', 'local_my');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

    $key = 'local_my/rendererimages';
    $label = get_string('rendererimages', 'local_my');
    $desc = get_string('rendererimages_desc', 'local_my');
    $options = array('subdirs' => false, 'maxfiles' => 20);
    $settings->add(new admin_setting_configstoredfile($key, $label, $desc, 'rendererimages', 0, $options));

    $settings->add(new admin_setting_heading('header5', get_string('visualsettings', 'local_my'), ''));

    $key = 'local_my/effect_opacity';
    $label = get_string('effectopacity', 'local_my');
    $desc = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));

    $key = 'local_my/effect_halo';
    $label = get_string('effecthalo', 'local_my');
    $desc = '';
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 0));
}
