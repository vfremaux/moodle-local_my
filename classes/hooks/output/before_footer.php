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

namespace local_my\hook\output;

class before_footer {

    public static function callback(\core\hook\output\before_footer $hook): void) {
        global $PAGE, $USER;

        $config = get_config('local_my');

        $systemcontext = context_system::instance();
        if (!empty($config->force) && !has_capability('local/my:overridemy', $systemcontext, $USER, false)) {
            $PAGE->requires->js_call_amd('local_my/local_my', 'hide_home_nav', [null]);
        }
    }
}