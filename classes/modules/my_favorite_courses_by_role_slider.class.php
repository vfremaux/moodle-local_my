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
namespace local_my\module;

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot.'/local/my/classes/modules/my_courses_slider.class.php');
require_once($CFG->dirroot.'/local/my/classes/modules/my_favorite_with_role.trait.php');

class my_favorite_courses_by_role_slider_module extends my_courses_slider_module {
    use my_favorite_with_role;

    public function __construct() {
        parent::__construct();
        $this->area = 'my_favorite_courses_by_role_slider';
        $this->extraclasses = 'favorite-courses';
        $this->modulename = get_string('myfavoritecourses', 'local_my');
        if (!self::$isslickrendered) {
            $renderer = self::get_renderer();
            $renderer->js_call_amd('local_my/slick', 'init');
            $renderer->js_call_amd('local_my/slickinit', 'init');
            $PAGE->requires->css('/local/my/css/slick.css');
            self::$isslickrendered = true;
        }

        $this->options['withteachersignals'] = true;
        $this->options['noprogress'] = false;
        $this->options['isfavorite'] = true;
        $this->options['noexcludefromstream'] = true;
    }
}