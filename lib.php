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
 */
defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot.'/mod/assign/locallib.php');

/**
 * Count users' assign to submit in course
 */
function local_my_count_assigns_to_submit($course, $userid = 0) {
    global $DB;

    $assigns = $DB->get_records('assign', ['course' => $course->id], 'id', 'id, name');
    $modinfo = get_fast_modinfo($course);

    $total = 0;
    $unsubmittedarr = [];
    foreach ($assigns as $assid => $ass) {
        $cm = get_coursemodule_from_instance('assign', $ass->id);
        if (!$cm) {
            continue;
        }
        $context = context_module::instance($cm->id);
        $cminfo = $modinfo->get_cm($cm->id);
        $assignment = new assign($context, $cminfo, $course);
        // Trap count if user is a student defined id.
        /*
        if (!\core_availability\info_module::is_user_visible($cm->id, $userid, false)) {
            continue;
        }
        */
        $submission = $assignment->get_user_submission($userid, false, 0);
        if (!$submission) {
            $unsubmittedarr[$assid] = $assid;
        }
        $total++;
    }
    $assignstocomplete = count($unsubmittedarr);
    return [$assignstocomplete, $total];
}

function local_my_count_expected_assignments($course) {
    global $DB;

    $total = 0;
    $assigns = $DB->get_records('assign', ['course' => $course->id]);
    $unsubmittedarr = [];
    $unsubmittedusersarr = [];
    $unsubmitteduserstotalarr = [];
    foreach ($assigns as $ass) {
        $cm = get_coursemodule_from_instance('assign', $ass->id);
        // Teacher case. Get submission count from assignment.
        $context = context_module::instance($cm->id);
        $assignment = new assign($context, $cm, $course);
        $submitters = get_users_by_capability($context, 'mod/assign:submit', 'u.id');
        foreach (array_keys($submitters) as $userid) {
            $submission = $assignment->get_user_submission($userid, false, 0);
            if (!$submission) {
                if (!array_key_exists($ass->id, $unsubmittedarr)) {
                    $unsubmittedarr[$ass->id] = 0;
                }
                $unsubmittedarr[$ass->id]++;
                $unsubmittedusersarr[$userid] = $userid;
            }
            $unsubmitteduserstotalarr[$userid] = $userid;
            $total++;
        }

    }
    return [array_sum(array_values($unsubmittedarr)), $total, count($unsubmittedusersarr), count($unsubmitteduserstotalarr)];
}

/**
 * Calculates the number of quiz to complete for a given user.
 * @param int $courseid
 * @param int $userid
 */
function local_my_count_quiz_to_complete($courseid, $userid) {
    global $DB;

    $quizzes = $DB->get_records('quiz', ['course' => $courseid], 'id', 'id, name');

    $total = 0;
    $unattemptedarr = [];
    foreach ($quizzes as $qid => $q) {
        $cm = get_coursemodule_from_instance('quiz', $q->id);
        if (!$cm) {
            continue;
        }
        $context = context_module::instance($cm->id);
        /*
        if (!\core_availability\info_module::is_user_visible($cm, $userid, false)) {
            continue;
        }
        */
        if (!$DB->count_records('quiz_attempts', ['userid' => $userid, 'quiz' => $q->id])) {
            $unattemptedarr[$qid] = $qid;
        }
        $total++;
    }

    return [count($unattemptedarr), $total];
}

/**
 * counts how many users in course still have quiz to attempt.
 * @param int $courseid
s */
function local_my_count_users_with_quiz_to_complete($courseid) {
    global $DB;

    $uncompleteusers = 0;
    $total = 0;

    $quizzes = $DB->get_records('quiz', ['course' => $courseid], 'id', 'id, name');
    if (!$quizzes) {
        return [0, 0];
    }

    $totalarr = [];
    $uncompleteusersarr = [];

    $modinfo = get_fast_modinfo($courseid);

    debug_trace('Examinating for '.count($quizzes).' quizzes');
    foreach ($quizzes as $q) {
        $cm = get_coursemodule_from_instance('quiz', $q->id);
        if (!$cm) {
            continue;
        }
        $cminfo = $modinfo->get_cm($cm->id);

        $context = context_module::instance($cm->id);
        $quizusers = get_users_by_capability($context, 'mod/quiz:attempt', 'u.id');
        $userids = array_keys($quizusers);
        debug_trace('Examinating for '.count($userids).' users');
        foreach ($userids as $userid) {
            if (!\core_availability\info_module::is_user_visible($cminfo, $userid, true)) {
                continue;
            }
            if (!$DB->count_records('quiz_attempts', ['userid' => $userid, 'quiz' => $q->id])) {
                $uncompleteusersarr[$userid] = $userid;
            }
            $totalarr[$userid] = $userid;
        }
    }

    return [count(array_keys($uncompleteusersarr)), count(array_keys($totalarr))];
}

