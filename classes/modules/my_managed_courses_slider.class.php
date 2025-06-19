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

require_once($CFG->dirroot.'/local/my/classes/modules/my_managed_courses.class.php');

class my_managed_courses_slider_module extends my_managed_courses_module {

    public function __construct() {
        global $PAGE;

        parent::__construct();
        $this->area = 'my_managed_courses_slider';
        if (!self::$isslickrendered) {
            $renderer = self::get_renderer();
            $renderer->js_call_amd('local_my/slick', 'init');
            $renderer->js_call_amd('local_my/slickinit', 'init');
            $PAGE->requires->css('/local/my/css/slick.css');
            self::$isslickrendered = true;
        }

        $this->options['gaugetype'] = (@$this->options['gaugetype'] != 'noprogress') ? 'sektor' : 'noprogress';
        $this->options['gaugewidth'] = '20';
        $this->options['gaugeheight'] = '20';
    }

    public function render($required = 'aslist') {
        return parent::render('asgrid');
    }
}