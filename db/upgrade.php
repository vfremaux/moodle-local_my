<?php
// This file keeps track of upgrades to 
// the dashboard block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

defined('MOODLE_INTERNAL') || die();

/**
 * @package local_my
 * @category local
 */

function xmldb_local_my_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $result = true;

    if ($oldversion < 2016010801) {
        local_my_move_settings();
        upgrade_plugin_savepoint(true, 2016010801, 'local', 'my');
    }

    // Moodle 2.0 break line

    return $result;
}

function local_my_move_settings() {
    global $CFG, $DB;

    $pattern = 'localmy';

    if ($configs = $DB->get_records_select('config', " name LIKE ? ", array($pattern.'%'))) {
        foreach ($configs as $cfg) {
            $key = str_replace($pattern, '', $cfg->name);
            set_config($key, $cfg->value, 'local_my');
            $DB->delete_records('config', array('name' => $cfg->name));
        }
    }
}