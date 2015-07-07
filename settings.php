<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { 
    // Needs this condition or there is error on login page.
    $settings = new admin_settingpage('local_my', get_string('pluginname', 'local_my'));
    $ADMIN->add('localplugins', $settings);

    $yesnooptions[0] = get_string('no');
    $yesnooptions[1] = get_string('yes');

    $settings->add(new admin_setting_configcheckbox('localmyenable', get_string('localmyenable', 'local_my'), get_string('localmyenabledesc', 'local_my'), 0));

    $settings->add(new admin_setting_configcheckbox('localmyforce', get_string('localmyforce', 'local_my'), get_string('localmyforcedesc', 'local_my'), 0));

    $settings->add(new admin_setting_configselect('localskipmymetas', get_string('localskipmymetas', 'local_my'), get_string('localskipmymetasdesc', 'local_my'), 0, $yesnooptions, PARAM_BOOL));

    $settings->add(new admin_setting_configselect('localmyprintcategories', get_string('localmyprintcategories', 'local_my'), get_string('localmyprintcategoriesdesc', 'local_my'), 0, $yesnooptions, PARAM_BOOL));

    $defaultmodules = "me\nmy_heatmap-L\nleft_edition_column\nmy_courses\nauthored_courses\ncourse_areas\navailable_courses\nlatestnews_simple";
    if (!isset($CFG->localmymodules)) {
        set_config('localmymodules', $defaultmodules);
    }
    $settings->add(new admin_setting_configtextarea('localmymodules', get_string('localmymodules', 'local_my'), get_string('localmymodulesdesc', 'local_my'), $defaultmodules));

    $options = array();
    $options[0] = get_string('nocourseareas', 'local_my');
    for ($i = 1 ; $i < 10 ; $i++) {
        $options[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('localmycourseareas', get_string('localmycourseareas', 'local_my'), get_string('localmycourseareasdesc', 'local_my'), 0, $options, PARAM_INT));

    global $SITE;

    $categoryoptions = coursecat::make_categories_list();
    $categoryoptions[0] = $SITE->fullname;
    asort($categoryoptions);
    for ($i = 0; $i < @$CFG->localmycourseareas; $i++) {
        $settings->add(new admin_setting_configselect('localmycoursearea'.$i, get_string('localmycoursearea', 'local_my').' '.$i, '', 0, $categoryoptions, PARAM_INT));
    }

    $heatmapoptions = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 8 => 8, 10 => 10, 12 => 12);
    $settings->add(new admin_setting_configselect('localmyheatmaprange', get_string('localmyheatmaprange', 'local_my'), get_string('localmyheatmaprangedesc', 'local_my'), 6, $heatmapoptions, PARAM_INT));

    $overviewedoptions = array(0 => 0, 5 => 5, 10 => 10, 20 => 20);
    $settings->add(new admin_setting_configselect('localmymaxoverviewedlistsize', get_string('localmymaxoverviewedlistsize', 'local_my'), get_string('localmymaxoverviewedlistsizedesc', 'local_my'), 10, $overviewedoptions, PARAM_INT));
}

