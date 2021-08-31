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

use \context_course;

/**
 * common code to all "favorite" wigdets
 */
trait my_favorite_with_role {
    public function get_courses() {
        global $USER, $DB, $CFG;

        $params = ['userid' => $USER->id, 'name' => 'local_my_favorite_courses'];
        $favorites = $DB->get_field('user_preferences', 'value', $params);
        if (empty($favorites)) {
            return;
        }
        $favoriteids = explode(',', $favorites);
        foreach ($favoriteids as $fc) {
            $this->courses[$fc] = $DB->get_record('course', ['id' => $fc]);
        }

        $this->process_excluded();
        $this->process_metas();
        $this->process_role_filtering();
        // $this->process_courseareas();
    }
}