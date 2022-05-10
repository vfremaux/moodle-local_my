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
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2013 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2021102100;   // The (date) version of this plugin.
$plugin->requires = 2019051100;   // Requires this Moodle version.
$plugin->component = 'local_my';
$plugin->release = '3.7.0 (Build 2021102100)';
$plugin->maturity = MATURITY_STABLE;

// Non moodle attributes.
$plugin->codeincrement = '3.7.0014';
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