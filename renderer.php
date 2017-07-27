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
        $str .= $this->editing_icon($course);
        $str .= '<a class="courselink" href="'.$courseurl.'">'.format_string($course->fullname).'</a>';
        if (!empty($options['withdescription'])) {
            $str .= '<p class="coursedescription">'.format_text($course->summary, $course->summaryformat).'</p>';
        }
        $str .= '</td>';

        if (empty($options['nocompletion'])) {
            if (!has_capability('local/my:isteacher', context_course::instance($course->id), $USER->id, false)) {
                // Only non teachers can see progression.
                $str .= $this->course_completion_gauge($course, 'td', $options['gaugewidth'], $options['gaugeheight']);
            }
        }

        $str .= '</tr>';

        return $str;
    }

    public function editing_icon(&$course) {
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $pixurl = $this->output->pix_url('editing', 'local_my');
            return $this->output->box('<img src="'.$pixurl.'" title="'.get_string('editing', 'local_my').'">', 'editing-icon pull-right');
        }
    }

    public function course_as_box($c) {
        $str = '';

        $context = context_course::instance($c->id);
        $courseurl = new moodle_url('/course/view.php?id='.$c->id);
        $fs = get_file_storage();

        $css = $c->visible ? '' : 'dimmed';

        $str .= '<div class="course-box '.$css.' pull-left">';
        $str .= '<div class="title"><a href="'.$courseurl.'" title="'.$c->fullname.'">'.$c->shortname.'</a>';
        $str .= $this->editing_icon($course);
        $str .= '</div>';

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
        $params = array('f' => $forum->id);
        $forumurl = new moodle_url('/mod/forum/view.php', $params);
        $attrs = array('href' => $forumurl);
        $str .= html_writer::tag('a', get_string('seeallnews', 'local_my'), $attrs);
        return $str;
    }

    /**
     * Prints tabs if separated role screens
     */
    public function tabs(&$view, $isteacher) {
        global $SESSION;

        $config = get_config('local_my');

        $systemcontext = context_system::instance();
        $isadmin = has_capability("moodle/site:config", $systemcontext) || has_capability("local/my:ismanager", $systemcontext);

        if (!empty($config->adminmodules) && $isadmin) {
            $tabname = get_string('asadmin', 'local_my');
            $params = array('view' => 'asadmin');
            $taburl = new moodle_url('/my', $params);
            $rows[0][] = new tabobject('asadmin', $taburl, $tabname);
        }

        if (empty($config->teachermodules)) {
            return;
        }

        if (empty($view)) {
            $view = @$SESSION->localmyview;

            if ($isadmin) {
                if (empty($view)) {
                    $view = 'asadmin';
                }
            } else if ($isteacher) {
                if (empty($view)) {
                    $view = 'asteacher';
                }
            } else {
                // Force anyway the student view only, including forcing session.
                // Do NOT print any tabs.
                $view = 'asstudent';
                return;
            }
        }

        $tabname = get_string('asteacher', 'local_my');
        $params = array('view' => 'asteacher');
        $taburl = new moodle_url('/my', $params);
        $rows[0][] = new tabobject('asteacher', $taburl, $tabname);

        $tabname = get_string('asstudent', 'local_my');
        $params = array('view' => 'asstudent');
        $taburl = new moodle_url('/my', $params);
        $rows[0][] = new tabobject('asstudent', $taburl, $tabname);

        return print_tabs($rows, $view, null, null, true);
    }

    public function courses_slider($courseids) {
        global $CFG, $PAGE;

        $totalfcourse = count($courseids);

        $featuredheader = '<div class="custom-courses-list">
                           <div class="container-fluid">
                           <div data-crow="'.$totalfcourse.'">';

        $featuredfooter = '';
        $featuredfooter .= '<div class="clearfix"></div>';
        $featuredfooter .= '</div>';
        $featuredfooter .= '</div>';
        $featuredfooter .= '</div>';

        $str = '';

        if (!empty($courseids)) {
            $rowcontent = '<div><div class="row-fluid local-my-carousel">';

            foreach ($courseids as $courseid) {

                $course = get_course($courseid);

                $summary = local_my_strip_html_tags($course->summary);
                $summary = local_my_course_trim_char($summary, 250);
                $trimtitle = local_my_course_trim_char($course->fullname, 40);

                $courseurl = new moodle_url('/course/view.php', array('id' => $courseid ));

                if ($course instanceof stdClass) {
                    require_once($CFG->libdir. '/coursecatlib.php');
                    $course = new course_in_list($course);
                }

                $imgurl = '';
                $context = context_course::instance($course->id);

                foreach ($course->get_course_overviewfiles() as $file) {
                    if ($isimage = $file->is_valid_image()) {
                        $path = '/'. $file->get_contextid(). '/'. $file->get_component().'/';
                        $path .= $file->get_filearea().$file->get_filepath().$file->get_filename();
                        $imgurl = file_encode_url("$CFG->wwwroot/pluginfile.php", $path, !$isimage);
                        break;
                    }
                }
                if (!$imgurl) {
                    $imgurl = $this->get_image_url('coursedefaultimage');
                }

                $rowcontent .= '<div class="local-my-promowrap">';
                $rowcontent .= '<div class="local-my-fp-coursebox">';
                $rowcontent .= '<div class="local-my-fp-coursethumb">';
                $rowcontent .= '<div class="local-my-fp-coursename">';
                $rowcontent .= '<a href="'.$courseurl.'">';
                $rowcontent .= '<img src="'.$imgurl.'" width="100%" height="125" title="'.$course->fullname.'">';
                $rowcontent .= '</a>';
                $rowcontent .= '</div>';
                $rowcontent .= '<div class="local-my-fp-courseinfo">';
                $rowcontent .= '<h5><a href="'.$courseurl.'" id="button" data-toggle="tooltip" data-placement="bottom" title="'.$course->fullname.'" >'.$trimtitle.'</a></h5>';
                $rowcontent .= '</div>';
                $rowcontent .= '<div class="local-my-fp-summary">'.$summary.'</div>';
                $rowcontent .= '</div>';
                $rowcontent .= '</div>';
                $rowcontent .= '</div>';
            }
            $rowcontent .= '</div></div>';
            $str .= $rowcontent;
        }

        return $featuredheader.$str.$featuredfooter;
    }

    protected function get_image_url($imgname) {
        global $PAGE;

        $fs = get_file_storage();

        $context = context_system::instance();

        $haslocalfile = false;
        $frec = new StdClass;
        $frec->contextid = $context->id;
        $frec->component = 'local_my';
        $frec->filearea = 'rendererimages';
        $frec->filename = $imgname.'.svg';
        if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
            $frec->filename = $imgname.'.png';
            if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                $frec->filename = $imgname.'.jpg';
                if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                    $frec->filename = $imgname.'.gif';
                    if ($fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                        $haslocalfile = true;
                    }
                } else {
                    $haslocalfile = true;
                }
            } else {
                $haslocalfile = true;
            }
        } else {
            $haslocalfile = true;
        }

        if ($haslocalfile) {
            $fileurl = moodle_url::make_pluginfile_url($frec->contextid, $frec->component, $frec->filearea, 0, '/',
                                                    $frec->filename, false);
            return $fileurl;
        }

        if ($PAGE->theme->resolve_image_location($imgname, 'theme', true)) {
            $imgurl = $this->output->pix_url($imgname, 'theme');
        } else {
            return $this->output->pix_url($imgname, 'local_my');
        }

        return $imgurl;
    }

    /**
     *
     *
     *
     */
    public function course_creator_buttons($mycatlist) {
        global $CFG, $OUTPUT, $USER, $DB;

        $str = '';

        if (!empty($mycatlist)) {

            $levels = CONTEXT_COURSECAT;
            $cancreate = local_my_has_capability_somewhere('moodle/course:create', false, false, true, $levels);

            $catids = array_keys($mycatlist);
            $firstcatid = array_shift($catids);
            $button0 = '';
            $button1 = '';
            $button2 = '';
            $button3 = '';

            if ($cancreate) {
                $params = array('view' => 'courses', 'categoryid' => $firstcatid);
                $label = get_string('managemycourses', 'local_my');
                $button0 = $OUTPUT->single_button(new moodle_url('/course/management.php', $params), $label);

                $label = get_string('newcourse', 'local_my');
                $button1 = $OUTPUT->single_button(new moodle_url('/local/my/create_course.php'), $label);

                if (is_dir($CFG->dirroot.'/local/coursetemplates')) {
                    $config = get_config('local_coursetemplates');
                    if ($config->enabled && $config->templatecategory) {
                        $params = array('category' => $config->templatecategory, 'visible' => 1);
                        if ($DB->count_records('course', $params)) {
                            $buttonurl = new moodle_url('/local/coursetemplates/index.php');
                            $button2 = $OUTPUT->single_button($buttonurl, get_string('newcoursefromtemplate', 'local_my'));
                        }
                    }
                }

                // Need fetch a context where user has effective capability.

                $powercontext = local_get_one_of_my_power_contexts();
                if ($powercontext) {
                    $params = array('contextid' => $powercontext->id);
                    $buttonurl = new moodle_url('/backup/restorefile.php', $params);
                    $button3 = $OUTPUT->single_button($buttonurl, get_string('restorecourse', 'local_my'));
                }
            }

            $str .= $OUTPUT->box_start('right-button course-creation-buttons');
            $str .= $button0.' '.$button1.' '.$button2.' '.$button3;
            $str .= $OUTPUT->box_end();
        }

        return $str;
    }

    public function site_stats() {
        global $CFG, $DB, $OUTPUT;

        $statsraw = get_config('local_my', 'sitestats');
        if (empty($statsraw)) {
            $compile = optional_param('compile', false, PARAM_BOOL);
            if ($compile) {
                // Avoid DoD attack by compiling continuously.
                require_sesskey();
                $task = new \local_my\task\compile_stats_task();
                $task->execute();
                $statsraw = get_config('local_my', 'sitestats');
            } else {
                $params = array('view' =>'asadmin', 'compile' => 1, 'sesskey' => sesskey());
                $forceurl = new moodle_url('/my/index.php', $params);
                return $OUTPUT->notification(get_string('notcompiledyet', 'local_my', ''.$forceurl));
            }
        }
        $stats = unserialize($statsraw);

        $str = '';

        $template = new StdClass;
        $template->label = get_string('filestorage', 'local_my');
        $template->value = sprintf('%0.2f', $stats->filesize / 1024 / 1024).' MB';
        $template->faicon = 'fa-pie-chart';
        $template->facolor = 'text-red';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $template = new StdClass;
        $template->label = get_string('numberoffiles', 'local_my');
        $template->value = $stats->numfiles;
        $template->faicon = 'fa-pie-chart';
        $template->facolor = 'text-orange';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $template = new StdClass;
        $template->label = get_string('enabledusers', 'local_my');
        $template->value =  0 + $stats->usercounters->active;
        $template->faicon = 'fa-users';
        $template->facolor = 'text-blue';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $template = new StdClass;
        $template->label = get_string('suspendedusers', 'local_my');
        $template->value = 0 + $stats->usercounters->suspended;
        $template->faicon = 'fa-users';
        $template->facolor = 'text-red';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $conratio = $stats->usercounters->connected / $stats->usercounters->active * 100;

        $template = new StdClass;
        $template->label = get_string('connectedusers', 'local_my');
        $template->value = 0 + $stats->usercounters->connected.' ('.sprintf('%.2f', $conratio).' %)';
        $template->faicon = 'fa-users';
        $template->facolor = 'text-green';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $select = " suspended = 0 AND deleted = 0 AND lastaccess > ? ";
        $onlineusers = $DB->count_records_select('user', $select, array(time() - 5 * MINSECS));

        $template = new StdClass;
        $template->label = get_string('onlineusers', 'local_my');
        $template->value = 0 + $onlineusers;
        $template->faicon = 'fa-users';
        $template->facolor = 'text-yellow';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $template = new StdClass;
        $template->label = get_string('opencourses', 'local_my');
        $template->value = 0 + $stats->coursecounters->visible;
        $template->faicon = 'fa-folder-open';
        $template->facolor = 'text-green';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        $template = new StdClass;
        $template->label = get_string('futurecourses', 'local_my');
        $template->value = 0 + $stats->coursecounters->future;
        $template->faicon = 'fa-folder-open';
        $template->facolor = 'text-red';
        $str .= $this->render_from_template('local_my/admin_overview_element', $template);

        return $str;
    }
}
