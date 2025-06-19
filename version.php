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
 * Version details.
 *
 * @package     local_my
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2013 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2025030601;   // The (date) version of this plugin.
$plugin->requires = 2022112801;   // Requires this Moodle version.
$plugin->component = 'local_my';
$plugin->release = '4.5.0 (Build 2025030601)';
$plugin->maturity = MATURITY_STABLE;
$plugin->supported = [401, 405];

// Non moodle attributes.
$plugin->codeincrement = '4.5.0017';
$plugin->privacy = 'dualrelease';
$plugin->profiles = [
    'classes/modules/course_areas.class.php',
    'classes/modules/course_areas_and_availables.class.php',
    'classes/modules/course_areas2.class.php',
    'classes/modules/my_templates.class.php',
    'classes/modules/my_network.class.php',
    'classes/modules/statictext.class.php',
    'classes/modules/available_courses_slider.class.php',
    'classes/modules/my_courses_slider.class.php',
    'classes/modules/my_authored_courses_slider.class.php',
    'classes/modules/my_managed_courses_slider.class.php',
    'classes/modules/my_favorite_courses_slider.class.php',
    'classes/modules/courses_slider.class.php',
];