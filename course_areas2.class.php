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

require_once($CFG->dirroot.'/local/my/classes/modules/course_areas.class.php');

use \StdClass;
use \moodle_url;

class course_areas2_module extends course_areas_module {

    public static $areakey = 'courseareas2';
    public static $areaconfigkey = 'coursearea2_';

    public function __construct() {
        parent::__construct();
        $this->area = 'course_areas2';
        $this->modulename = get_string('courseareas', 'local_my');

        self::$areakey = 'courseareas2';
        self::$areaconfigkey = 'coursearea2_';
    }

}