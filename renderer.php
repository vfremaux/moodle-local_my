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
 *
 * this is  aplugin overridable renderer for enhanced my dashboard page
 */
defined('MOODLE_INTERNAL') || die();

class local_my_renderer extends plugin_renderer_base {

    public function course_completion_gauge(&$course, $div, $width = 160, $height = 160, $type = 'progressbar') {
        global $USER, $PAGE;

        $str = '';

        $completion = new completion_info($course);
        if ($completion->is_enabled(null)) {
            $alltracked = count($completion->get_activities());
            $progressinfo = $completion->get_progress_all('u.id = :userid', array('userid' => $USER->id));
            $completed = 0;
            if (!empty($progressinfo)) {
                if (!empty($progressinfo[$USER->id]->progress)) {
                    foreach ($progressinfo[$USER->id]->progress as $progressrecord) {
                        if ($progressrecord->completionstate) {
                            $completed++;
                        }
                    }
                }
            }
            $ratio = ($alltracked == 0) ? 0 : round($completed / $alltracked * 100);
            $jqwrenderer = $PAGE->get_renderer('local_vflibs');

            if ($div == 'div') {
                $str .= '<div class="course-completion" title="'.get_string('completion', 'local_my', (0 + $ratio)).'">';
            } else {
                $str .= '<td class="course-completion" title="'.get_string('completion', 'local_my', (0 + $ratio)).'">';
            }

            if ($type == 'gauge') {
                $properties = array('width' => $width, 'height' => $height, 'max' => 100, 'crop' => 120);
                $str .= $jqwrenderer->jqw_bargauge_simple('completion-jqw-'.$course->id, array($ratio), $properties);
            } else {
                $properties = array('width' => $width, 'height' => $height, 'animation' => 300, 'template' => 'success');
                $str .= $jqwrenderer->jqw_progress_bar('completion-jqw-'.$course->id, $ratio, $properties);
            }

            if ($div == 'div') {
                $str .= '</div>';
            } else {
                $str .= '</td>';
            }
        } else {
            if ($div == 'div') {
                $str .= '<div class="course-completion">';
            } else {
                $str .= '<td class="course-completion">';
            }
            if ($div == 'div') {
                $str .= '</div>';
            } else {
                $str .= '</td>';
            }
        }

        return $str;
    }

    public function course_simple_div($course, $classes = '') {
        $str = '';
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $link = '<a class="courselink" href="'.$courseurl.'">'.format_string($course->fullname).'</a>';
        $str .= '<div class="courseinfo '.$classes.'">'.$link.'</div>';
        return $str;
    }

    public function course_table_row($course, $options) {
        global $DB, $USER;

        $str = '';

        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));

        if (!isset($course->summary)) {
            $course->summary = $DB->get_field('course', 'summary', array('id' => $course->id));
            $course->summaryformat = $DB->get_field('course', 'summaryformat', array('id' => $course->id));
        }

        $str .= '<tr valign="top">';
        $str .= '<td class="courserow">';
        $str .= '<a class="courselink" href="'.$courseurl.'">'.format_string($course->fullname).'</a>';
        if (!empty($options['withdescription'])) {
            $str .= '<p class="coursedescription">'.format_text($course->summary, $course->summaryformat).'</p>';
        }
        $str .= '</td>';

        if (empty($options['nocompletion'])) {
            if (!has_capability('moodle/grade:viewall', context_course::instance($course->id), $USER->id, false)) {
                $str .= $this->course_completion_gauge($course, 'td', $options['gaugewidth'], $options['gaugeheight']);
            }
        }

        $str .= '</tr>';

        return $str;
    }

    public function course_as_box($c) {
        $str = '';

        $context = context_course::instance($c->id);
        $courseurl = new moodle_url('/course/view.php?id='.$c->id);
        $fs = get_file_storage();

        $css = $c->visible ? '' : 'dimmed';

        $str .= '<div class="course-box '.$css.' pull-left">';
        $str .= '<div class="title"><a href="'.$courseurl.'" title="'.$c->fullname.'">'.$c->shortname.'</a></div>';

        $context = context_course::instance($c->id);
        $images = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        if ($image = array_pop($images)) {
            $coursefileurl = moodle_url::make_pluginfile_url($context->id, 'course', 'overviewfiles', '',
                                                             $image->get_filepath(), $image->get_filename());
            $str .= '<div class="courseimage" style="background-image:url('.$coursefileurl.');background-size:cover">';
            $str .= '<a href="'.$courseurl.'">&nbsp;</a>';
            $str .= '</div>';
        } else {
            $str .= '<div class="summary">'.shorten_text(format_string($c->summary), 80).'</div>';
        }

        $str .= '</div>';

        return $str;
    }

    public function print_forum_link($forum, &$forumname) {
        global $SITE;

        // Fetch news forum context for proper filtering to happen.
        $newsforumcm = get_coursemodule_from_instance('forum', $forum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $str = '';

        $forumname = format_string($forum->name, true, array('context' => $newsforumcontext));
        $attrs = array('href' => '#skipsitenews', 'class' => 'skip-block');
        $str .= html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(strip_tags($forumname))), $attrs);

        return $str;
    }
}
