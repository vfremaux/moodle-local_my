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

require_once($CFG->dirroot.'/local/my/lib.php');

class local_my_renderer extends plugin_renderer_base {

    /**
     * Prints a progression progress bar or gauge in a div or a table cell
     * @param objectref $course
     * @param int $width default width
     * @param int $heigh default height
     * @param string $progressbar type of gauge renderer.
     */
    public function course_completion_gauge(&$course, $type, $options = []) {
        global $USER, $PAGE, $DB, $CFG;

        if ($type == 'noprogress') {
            return '';
        }

        $courserec = $DB->get_record('course', array('id' => $course->id)); // Get a mutable object.
        $completion = new completion_info($courserec);
        if (!$completion->is_enabled(null)) {
            return '';
        }

        $progression = '';

        $ratio = round(\core_completion\progress::get_course_progress_percentage($courserec));

        if ($type == 'gauge') {
            $jqwrenderer = $PAGE->get_renderer('local_vflibs');
            $properties = array('width' => $options['gaugewidth'], 'height' => $options['gaugeheight'], 'max' => 100, 'crop' => 120);
            $progression = $jqwrenderer->jqw_bargauge_simple('completion-jqw-'.$course->id,
                                                                       array($ratio), $properties);
        } else if ($type == 'progressbar') {
            $jqwrenderer = $PAGE->get_renderer('local_vflibs');
            $properties = ['width' => $options['gaugewidth'], 'height' => $options['gaugeheight'], 'animation' => 300, 'template' => 'success'];
            $progression = $jqwrenderer->jqw_progress_bar('completion-jqw-'.$course->id,
                                                                    $ratio, $properties);
        } else if ($type == 'jqplot') {
            include_once($CFG->dirroot . "/local/vflibs/jqplotlib.php");
            local_vflibs_require_jqplot_libs();
            // Completion with a donut.
            $completedstr = get_string('completion', 'local_my', $ratio);
            $data = array(array($completedstr, $ratio), array('', round(100 - $ratio)));
            $attrs = array('width' => $options['gaugewidth'], 'height' => $options['gaugeheight']);
            $progression = local_vflibs_jqplot_simple_donut($data, 'course_completion_'.$course->id, 'completion-jqw-'.$course->id, $attrs);
        } else if ($type == 'sektor') {
            $progresstpl = new StdClass;
            $progresstpl->completionstr = get_string('completion', 'local_my', 0 + $ratio);
            $progresstpl->id = $course->id;
            $progresstpl->ratio = $ratio;
            $progresstpl->collapseclass = @$options['collapseclass'];
            $progression = $this->output->render_from_template('local_my/courseprogression_sektor', $progresstpl);
            $sektorparams = array(
                'id' => '#sektor-progress-'.$course->id,
                'angle' => round($ratio * 360 / 100),
                'size' => $options['gaugewidth'],
                // height not used.
            );
            $PAGE->requires->js_call_amd('local_my/local_my', 'sektor', array($sektorparams));
        } else {
            if ($ratio) {
                $comppercent = number_format($ratio, 0);
                $hasprogress = true;
            } else {
                $comppercent = 0;
                $hasprogress = false;
            }
            $progresschartcontext = [
                'completionstr' => get_string('completion', 'local_my', 0 + $ratio),
                'hasprogress' => $hasprogress,
                'progress' => $comppercent
            ];
            $progression = $this->output->render_from_template('local_my/progress-chart', $progresschartcontext);
        }

        return $progression;
    }

    /*
    public function course_table_row($course, $options) {
        global $DB, $USER;

        $config = get_config('local_my');

        $template = new StdClass;

        $template->courseurl = new moodle_url('/course/view.php', array('id' => $course->id));

        if (!isset($course->summary)) {
            $course->summary = $DB->get_field('course', 'summary', array('id' => $course->id));
            $course->summaryformat = $DB->get_field('course', 'summaryformat', array('id' => $course->id));
        }

        $template->editingicon = $this->editing_icon($course);
        if (!empty($config->showcourseidentifier)) {
            if ($config->showcourseidentifier == 'idnumber') {
                $template->cid = $course->idnumber;
            } else {
                $template->cid = $course->shortname;
            }
        }
        $template->fullname = format_string($course->fullname);
        $context = context_course::instance($course->id);
        $summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);
        $template->summary = format_text($summary);
        if (!empty($options['withdescription'])) {
            $template->hasdescription = true;
        }

        // Only non teachers can see progression.
        if (!array_key_exists('gaugewidth', $options)) {
            debugging('Missing option');
        }
        if (!array_key_exists('gaugetype', $options)) {
            debugging('Missing option');
        }
        $this->course_completion_gauge($course, $options['gaugewidth'], $options['gaugeheight'],
                                       $options['gaugetype'], $template);

        $template->hiddenclass = (local_my_is_visible_course($course)) ? '' : 'dimmed';
        $template->selfenrolclass = (local_my_is_selfenrolable_course($course)) ? 'selfenrol' : '';
        $template->guestenrolclass = (local_my_is_guestenrolable_course($course)) ? 'guestenrol' : '';

        return $this->output->render_from_template('local_my/coursetablerow', $template);
    }
    */

    public function editing_icon(&$course) {
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $attrs = array('aria-label' => get_string('editing', 'local_my'));
            $pix = $this->output->pix_icon('editing', get_string('editing', 'local_my'), 'local_my', $attrs);
            return '<div class="editing-icon pull-right">'.$pix.'</div>';
        }
    }

    /**
     * Print a list style course list
     */
    /*
    public function courses_list($title = 'mycourses', $courses, $options = array()) {
        global $OUTPUT, $DB, $PAGE;

        $config = get_config('local_my');

        $str = '';

        // Be sure we have something in lastaccess.
        foreach ($courses as $cid => $c) {
            $courses[$cid]->lastaccess = 0 + @$courses[$cid]->lastaccess;
        }

        if (empty($courses)) {
            if (!empty($options['printifempty']) && empty($options['noheading'])) {
                $str .= $OUTPUT->box_start('header');
                $str .= $OUTPUT->box_start('title');
                $str .= '<h2>'.get_string($title, 'local_my').'</h2>';
                $str .= $OUTPUT->box_end();
                $str .= $OUTPUT->box_end();
                $str .= $OUTPUT->box(get_string('nocourses', 'local_my'), 'content');
            }
        } else {
            if (empty($options['noheading'])) {
                $str .= $OUTPUT->box_start('header');
                $str .= $OUTPUT->box_start('title');
                $str .= '<h2>'.get_string($title, 'local_my').'</h2>';
                $str .= $OUTPUT->box_end();
                $str .= $OUTPUT->box_end();
                $str .= $OUTPUT->box_start('content');
            }

            $str .= '<table class="courselist" width="100%">';
            if (!empty($options['withoverview'])) {
                // Old overviewed. OBSOLETE.
                // $str .= $renderer->course_overview($courses, $options);
            } else if (!empty($options['withcats'])) {
                // Structured list.
                $str .= $this->courses_by_cats($courses, $options, $title);
            } else {
                // Flat list.
                foreach ($courses as $c) {
                    $c->idnumber = $DB->get_field('course', 'idnumber', array('id' => $c->id));
                    $str .= $this->course_table_row($c, $options);
                }
            }
            $str .= '</table>';

            if (empty($options['noheading'])) {
                $str .= $OUTPUT->box_end();
            }
        }

        return $str;
    }
    */

    /*
    public function course_as_box($c) {

        $config = get_config('local_my');

        $template = new StdClass;

        $context = context_course::instance($c->id);
        $template->courseurl = new moodle_url('/course/view.php', array('id' => $c->id));
        $fs = get_file_storage();

        $template->css = $c->visible ? '' : 'dimmed';
        $template->fullname = format_string($c->fullname);
        if ($config->trimmode == 'words') {
            if (empty($config->trimlength1)) {
                $config->trimlength1 = 20;
            }
            $template->fullname = local_my_course_trim_words($template->fullname, $config->trimlength1);
        } else if ($config->trimmode == 'chars') {
            if (empty($config->trimlength1)) {
                $config->trimlength1 = 80;
            }
            $template->fullname = local_my_course_trim_char($template->fullname, $config->trimlength1);
        }

        $template->shortname = $c->shortname;

        $template->editingicon = $this->editing_icon($c);

        $context = context_course::instance($c->id);
        $images = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        if ($image = array_pop($images)) {
            $coursefileurl = moodle_url::make_pluginfile_url($context->id, 'course', 'overviewfiles', '',
                                                             $image->get_filepath(), $image->get_filename());
            $template->coursefileurl = $coursefileurl;
            $template->hasimage = true;
        } else {
            $template->summary = format_text($c->summary);
            if ($config->trimmode == 'words') {
                if (empty($config->trimlength2)) {
                    $config->trimlength2 = 100;
                }
                $template->summary = local_my_course_trim_words($template->summary, $config->trimlength2);
            } else if ($config->trimmode == 'chars') {
                if (empty($config->trimlength2)) {
                    $config->trimlength2 = 500;
                }
                $template->summary = local_my_course_trim_char($template->summary, $config->trimlength2);
            }
        }

        $this->course_completion_gauge($course,
                                       $config->progressgaugewidth, $config->progressgaugeheight,
                                       $config->progressgaugetype, $template);

        return $this->output->render_from_template('local_my/coursebox', $template);
    }
    */

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
     * Prints tabs if separated role screens.
     * view is assumed being adequately tuned and resolved.
     */
    public function tabs($view, $isstudent, $isteacher, $iscoursemanager, $isadmin) {
        global $SESSION;

        $config = get_config('local_my');

        $hasadmintab = false;
        if (!local_my_is_panel_empty('adminmodules') && $isadmin) {
            $tabname = get_string('asadmin', 'local_my');
            $params = array('view' => 'asadmin');
            $taburl = new moodle_url('/my/index.php', $params);
            $rows[0][] = new tabobject('asadmin', $taburl, $tabname);
        }

        if (!local_my_is_panel_empty('coursemanagermodules') && $iscoursemanager) {
            $tabname = get_string('ascoursemanager', 'local_my');
            $params = array('view' => 'ascoursemanager');
            $taburl = new moodle_url('/my/index.php', $params);
            $rows[0][] = new tabobject('ascoursemanager', $taburl, $tabname);
        }

        if (!local_my_is_panel_empty('teachermodules') && $isteacher) {
            $tabname = get_string('asteacher', 'local_my');
            $params = array('view' => 'asteacher');
            $taburl = new moodle_url('/my/index.php', $params);
            $rows[0][] = new tabobject('asteacher', $taburl, $tabname);
        }

        $canhaveavailablecourses = preg_match('/available/', $config->modules) || preg_match('/area/', $config->modules);

        if ($isstudent || $canhaveavailablecourses) {
            $tabname = get_string('asstudent', 'local_my');
            $params = array('view' => 'asstudent');
            $taburl = new moodle_url('/my/index.php', $params);
            $rows[0][] = new tabobject('asstudent', $taburl, $tabname);
        }

        if (!empty($rows) && count($rows[0]) > 1) {
            // Do not print anything if only one tab.
            return print_tabs($rows, $view, null, null, true);
        }

        return '';
    }

    public function courses_slider($courseids) {
        global $CFG, $PAGE;

        $template = new StdClass;

        $template->totalfcourse = count($courseids);

        if (!empty($courseids)) {
            foreach ($courseids as $courseid) {
                $coursetpl = $this->coursebox($courseid);
                $template->courses[] = $coursetpl;
            }
        }

        return $template;
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

                $label = get_string('allcategories', 'local_my');
                $button00 = $OUTPUT->single_button(new moodle_url('/course/index.php'), $label);

                $params = array('view' => 'courses', 'categoryid' => $firstcatid);
                $label = get_string('managemycourses', 'local_my');
                $button0 = $OUTPUT->single_button(new moodle_url('/local/my/management.php', $params), $label);

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
            $str .= $button00.' '.$button0.' '.$button1.' '.$button2.' '.$button3;
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
                // Avoid DoS attack by compiling continuously.
                require_sesskey();
                $task = new \local_my\task\compile_stats_task();
                $task->execute();
                $statsraw = get_config('local_my', 'sitestats');
            } else {
                $params = array('view' => 'asadmin', 'compile' => 1, 'sesskey' => sesskey());
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
        $template->value = 0 + $stats->usercounters->active;
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

    public function add_category_link($categoryid) {
        $template = new StdClass;

        $template->addsubcaturl = new moodle_url('/course/editcategory.php', array('parent' => $categoryid));
        $template->newsubcategorystr = get_string('addnewsubcategory', 'local_my');
        return $this->render_from_template('local_my/add_category_link', $template);
    }
}
