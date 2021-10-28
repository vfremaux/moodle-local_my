<?php
// This file is NOT part of Moodle - http://moodle.org/
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
 *
 * this is  aplugin overridable renderer for enhanced my dashboard page
 */

namespace local_my\module;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/my/pro/lib.php');

class pro_modules_additions {

    public static function add_student_indicators(&$coursetpl, &$course) {
        global $PAGE, $USER, $DB;

        $coursetpl->hasindicators = true;
        $renderer = $PAGE->get_renderer('local_my');

        // Assign signals
        $courserec = $DB->get_record('course', ['id' => $course->id]);
        list($unsubmitted, $total) = local_my_count_assigns_to_submit($courserec, $USER->id);
        $coursetpl->hasassignments = false;
        if ($total) {
            $coursetpl->hasassignments = true;
            $sektorparams = [
                'id' => '#sektor-assignments-'.$course->id,
                'angle' => round($unsubmitted * 360 / $total),
                'size' => 20,
                'circlecolor' => '#00bb00',
                'color' => '#dd0000',
                // height not used.
            ];
            $renderer->js_call_amd('local_my/local_my', 'sektor', [$sektorparams]);
            $coursetpl->assignmentssubmitteddata = $total - $unsubmitted;
            $coursetpl->assignmentstotaldata = $total;
            $coursetpl->assignmentsunsubmitteddata = $unsubmitted;
            $coursetpl->assignmentstosubmit = ($unsubmitted).' / '.$total;
        }

        // Quiz signals
        list($unattempted, $total) = local_my_count_quiz_to_complete($course->id, $USER->id);

        $coursetpl->hasquizspending = false;
        if ($total) {
            $coursetpl->hasquizspending = true;
            $sektorparams = [
                'id' => '#sektor-quizs-'.$course->id,
                'angle' => round($unattempted * 360 / $total),
                'size' => 20,
                'circlecolor' => '#00bb00',
                'color' => '#dd0000',
                // height not used.
            ];
            $renderer->js_call_amd('local_my/local_my', 'sektor', [$sektorparams]);
            $coursetpl->quizzes = ($unattempted).' / '.$total;
            $coursetpl->quizzestotal = $total;
        }
    }

    public static function add_teacher_indicators(&$coursetpl, &$course) {
        global $PAGE, $DB;

        $coursetpl->hasindicators = true;
        $renderer = $PAGE->get_renderer('local_my');

        // Assign signals (count submissions).
        $courserec = $DB->get_record('course', ['id' => $course->id]);
        list($unsubmitted, $submissionstotal, $distinctunsubmittedusers, $userstotal) = local_my_count_expected_assignments($courserec);

        if ($submissionstotal) {
            $coursetpl->hasassignments = true;
            $coursetpl->hasdetails = true;
            $coursetpl->assignmentstosubmit = $unsubmitted;
            $coursetpl->submissionstotal = $submissionstotal;
            $coursetpl->assignuserstotal = $userstotal;
            $coursetpl->distinctunsubmittedusers = $distinctunsubmittedusers;
            $coursetpl->assignmentstosubmitratio = round($unsubmitted / $submissionstotal * 100);
            $sektorparams = [
                'id' => '#sektor-missing-submissions-'.$course->id,
                'angle' => round(($submissionstotal - $unsubmitted) * 360 / $submissionstotal),
                'size' => 20,
                'color' => '#00cc00',
                'circlecolor' => '#dd0000',
                // height not used.
            ];
            $renderer->js_call_amd('local_my/local_my', 'sektor', [$sektorparams]);
        }

        // Quiz signals (count attempts for all.
        list($uncomplete, $total) = local_my_count_users_with_quiz_to_complete($course->id);

        if ($coursetpl->enrolled) {
            $coursetpl->hasquizuserspending = true;
            $coursetpl->hasdetails = true;
            $coursetpl->uncompletequizusers = $uncomplete;
            $coursetpl->uncompletequizusersratio = ($total) ? round($uncomplete / $total * 100) : 0;
            $coursetpl->quizuserstotal = $total;
            $coursetpl->completequizusersratio = round(($coursetpl->enrolled - $uncomplete) / $coursetpl->enrolled * 100);
            $sektorparams = [
                'id' => '#sektor-unattempted-quiz-users-'.$course->id,
                'angle' => round(($coursetpl->enrolled - $uncomplete) * 360 / $coursetpl->enrolled),
                'size' => 20,
                'color' => '#dd0000',
                'circlecolor' => '#00bb00',
                // height not used.
            ];
            $renderer->js_call_amd('local_my/local_my', 'sektor', [$sektorparams]);
        }
    }
}
