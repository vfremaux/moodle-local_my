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

    /**
     * Prints a progression progress bar or gauge in a div or a table cell
     * @param objectref $course
     * @param string $div 'div' if the result needs being tableless
     * @param int $width default width
     * @param int $heigh default height
     * @param string $progressbar type of gauge renderer.
     */
    public function course_completion_gauge(&$course, $div, $width = 160, $height = 160, $type = 'progressbar', &$template) {
        global $USER, $PAGE, $DB;

        $courserec = $DB->get_record('course', array('id' => $course->id)); // Get a mutable object.
        $completion = new completion_info($courserec);
        if ($completion->is_enabled(null)) {
            $ratio = \core_completion\progress::get_course_progress_percentage($courserec);
            $jqwrenderer = $PAGE->get_renderer('local_vflibs');

            $template->completionstr = get_string('completion', 'local_my', (0 + $ratio));

            if ($type == 'gauge') {
                $properties = array('width' => $width, 'height' => $height, 'max' => 100, 'crop' => 120);
                $template->progression = $jqwrenderer->jqw_bargauge_simple('completion-jqw-'.$course->id,
                                                                           array($ratio), $properties);
            } else if ($type == 'progressbar') {
                $properties = array('width' => $width, 'height' => $height, 'animation' => 300, 'template' => 'success');
                $template->progression = $jqwrenderer->jqw_progress_bar('completion-jqw-'.$course->id,
                                                                        $ratio, $properties);
            } else if ($type == 'jqplot') {
                // Completion with a donut.
                $completedstr = get_string('completion', 'local_my', $ratio);
                $data = array(array($completedstr, round($ratio)), array('', round(100 - $ratio)));
                $attrs = array('height' => $width, 'width' => $height);
                $template->progression = local_vflibs_jqplot_simple_donut($data, 'course_completion_'.$course->id, 'completion-jqw-'.$course->id, $attrs);
            } else {
                if ($ratio) {
                    $comppercent = number_format($ratio, 0);
                    $hasprogress = true;
                } else {
                    $comppercent = 0;
                    $hasprogress = false;
                }
                $progresschartcontext = ['hasprogress' => $hasprogress, 'progress' => $comppercent];
                $template->progression = $this->render_from_template('block_myoverview/progress-chart', $progresschartcontext);
            }
        } else {
            $template->progression = '';
        }
        // Just let data in incoming template.
    }

    /**
     * Deprecated : Used by no one.
     */
    public function course_simple_div($course, $classes = '') {

        return "Is deprecated";

        $str = '';
        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        $link = '<a class="courselink" href="'.$courseurl.'">'.format_string($course->fullname).'</a>';
        $str .= '<div class="courseinfo '.$classes.'">'.$link.'</div>';

        return $str;
    }

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

        if (empty($options['nocompletion'])) {
            if (!has_capability('local/my:isteacher', context_course::instance($course->id), $USER->id, false)) {
                // Only non teachers can see progression.
                if (!array_key_exists('gaugewidth', $options)) {
                    debugging('Missing option');
                }
                $this->course_completion_gauge($course, 'td', $options['gaugewidth'], $options['gaugeheight'],
                                               'progressbar', $template);
            }
        }

        $template->hiddenclass = (local_my_is_visible_course($course)) ? '' : 'dimmed';
        $template->selfenrolclass = (local_my_is_selfenrolable_course($course)) ? 'selfenrol' : '';
        $template->guestenrolclass = (local_my_is_guestenrolable_course($course)) ? 'guestenrol' : '';

        return $this->output->render_from_template('local_my/coursetablerow', $template);
    }

    public function editing_icon(&$course) {
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $pix = $this->output->pix_icon('editing', get_string('editing', 'local_my'), 'local_my');
            return $this->output->box($pix, 'editing-icon pull-right');
        }
    }

    public function course_as_box($c) {

        $template = new StdClass;

        $context = context_course::instance($c->id);
        $template->courseurl = new moodle_url('/course/view.php', array('id' => $c->id));
        $fs = get_file_storage();

        $template->css = $c->visible ? '' : 'dimmed';
        $template->fullname = format_string($c->fullname);
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
            $template->summary = shorten_text(format_text($c->summary), 80);
        }

        $this->course_completion_gauge($course, 'div', 
                                       $options['gaugewidth'], $options['gaugeheight'],
                                       'donut', $template);

        return $this->output->render_from_template('local_my/coursebox', $template);
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
    public function tabs(&$view, $isteacher, $iscoursemanager) {
        global $SESSION;

        $config = get_config('local_my');

        $systemcontext = context_system::instance();
        $isadmin = has_capability("moodle/site:config", $systemcontext) || has_capability("local/my:ismanager", $systemcontext);

        if (!empty($config->adminmodules) && $isadmin) {
            $tabname = get_string('asadmin', 'local_my');
            $params = array('view' => 'asadmin');
            $taburl = new moodle_url('/my/index.php', $params);
            $rows[0][] = new tabobject('asadmin', $taburl, $tabname);
        }

        if (!empty($config->coursemanagermodules) && $iscoursemanager) {
            $tabname = get_string('ascoursemanager', 'local_my');
            $params = array('view' => 'ascoursemanager');
<<<<<<< HEAD
            $taburl = new moodle_url('/my', $params);
=======
            $taburl = new moodle_url('/my/index.php', $params);
>>>>>>> MOODLE_36_STABLE
            $rows[0][] = new tabobject('ascoursemanager', $taburl, $tabname);
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
        $taburl = new moodle_url('/my/index.php', $params);
        $rows[0][] = new tabobject('asteacher', $taburl, $tabname);

        $tabname = get_string('asstudent', 'local_my');
        $params = array('view' => 'asstudent');
        $taburl = new moodle_url('/my/index.php', $params);
        $rows[0][] = new tabobject('asstudent', $taburl, $tabname);

        return print_tabs($rows, $view, null, null, true);
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

        return $this->output->render_from_template('local_my/course_slider', $template);
    }

    public function coursebox($courseorid) {
        global $CFG, $USER;

        if (is_object($courseorid)) {
            $courseid = $courseorid->id;
        } else {
            $courseid = $courseorid;
        }

        $coursetpl = new StdClass;
        $course = get_course($courseid);
        $context = context_course::instance($course->id);
        $summary = local_my_strip_html_tags($course->summary);
        $coursetpl->summary = local_my_course_trim_char($summary, 250);
        $coursetpl->fullname = format_string($course->fullname);
        $coursetpl->trimtitle = local_my_course_trim_char(format_string($course->fullname), 80);

        $courseurl = new moodle_url('/course/view.php', array('id' => $courseid ));
        $coursetpl->courseurl = ''.$courseurl;

        $coursetpl->hasattributes = false;
        if (local_my_is_visible_course($course)) {
            $coursetpl->hiddenclass = '';
            $coursetpl->hiddenattribute = '';
        } else {
            $coursetpl->hasattributes = true;
            $coursetpl->hiddenattribute = $this->output->pix_icon('hidden', get_string('ishidden', 'local_my'), 'local_my');
            $coursetpl->hiddenclass = 'dimmed';
        }
        if (has_capability('moodle/course:manageactivities', $context, $USER, false)) {
            $coursetpl->hasattributes = true;
            $coursetpl->editingclass = 'can-edit';
            $coursetpl->editingattribute = $this->output->pix_icon('editing', get_string('canedit', 'local_my'), 'local_my');
        } else {
            $coursetpl->editingclass = '';
            $coursetpl->editingattribute = '';
        }
        if (local_my_is_selfenrolable_course($course)) {
            $coursetpl->hasattributes = true;
            $coursetpl->selfenrolclass = 'selfenrol';
            $coursetpl->selfattribute = $this->output->pix_icon('unlocked', get_string('selfenrol', 'local_my'), 'local_my');
        } else {
            $coursetpl->selfenrolclass = '';
            $coursetpl->selfattribute = '';
        }
        if (local_my_is_guestenrolable_course($course)) {
            $coursetpl->hasattributes = true;
            $coursetpl->guestenrolclass = 'guestenrol';
            $coursetpl->guestattribute = $this->output->pix_icon('guest', get_string('guestenrol', 'local_my'), 'local_my');
        } else {
            $coursetpl->guestenrolclass = '';
            $coursetpl->guestattirbute = '';
        }
        if ($course->startdate > time()) {
            $coursetpl->hasattributes = true;
            $coursetpl->futureclass = 'future';
            $coursetpl->futureattribute = $this->output->pix_icon('future', get_string('future', 'local_my'), 'local_my');
        } else {
            $coursetpl->futureclass = '';
            $coursetpl->futureattribute = '';
        }

        if ($course instanceof stdClass) {
            $course = new \core_course_list_element($course);
        }

<<<<<<< HEAD
                $this->course_completion_gauge($course, 'div', 
                                       120, 120,
                                       'donut', $coursetpl);

                $template->courses[] = $coursetpl;
=======
        $context = context_course::instance($course->id);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($isimage = $file->is_valid_image()) {
                $path = '/'. $file->get_contextid(). '/'. $file->get_component().'/';
                $path .= $file->get_filearea().$file->get_filepath().$file->get_filename();
                $coursetpl->imgurl = ''.file_encode_url("$CFG->wwwroot/pluginfile.php", $path, !$isimage);
                break;
>>>>>>> MOODLE_36_STABLE
            }
        }
        if (empty($coursetpl->imgurl)) {
            $coursetpl->imgurl = ''.$this->get_image_url('coursedefaultimage');
        }

        $this->course_completion_gauge($course, 'div', 120, 120, 'donut', $coursetpl);

        return $coursetpl;
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
            $imgurl = $this->output->image_url($imgname, 'theme');
        } else {
            return $this->output->image_url($imgname, 'local_my');
        }

        return $imgurl;
    }

    /**
     * Print a simple list of coures with first level category caption
     */
    public function courses_by_cats($courselist, $options = array(), $area = '') {
        global $CFG, $DB, $USER, $OUTPUT, $PAGE;

        $renderer = $PAGE->get_renderer('local_my');

        // Get user preferences for collapser.
        $select = " userid = ? and name LIKE 'local_my%' ";
        $params = array('userid' => $USER->id);
        $collapses = $DB->get_records_select_menu('user_preferences', $select, $params, 'name,value', 'name,value');

        // Reorganise by cat.
        foreach ($courselist as $c) {
            if (!isset($catcourses[$c->category])) {
                $catcourses[$c->category] = new StdClass;
                $catcourses[$c->category]->category = $DB->get_record('course_categories', array('id' => $c->category));
            }
            $catcourses[$c->category]->courses[] = $c;
        }

        $template = new StdClass;
        $template->area = $area;
        $template->collapseallstr = get_string('collapseall', 'local_my');
        $template->expandallstr = get_string('expandall', 'local_my');
        $template->catidlist = implode(',', array_keys($catcourses));

        foreach ($catcourses as $catid => $cat) {

            if (!$catid) {
                continue;
            }

            $cattpl = new Stdclass;
            $cattpl->catid = $cat->category->id;

            $catcontext = context_coursecat::instance($catid);
            if (array_key_exists('local_my_'.$area.'_'.$catid.'_hidden', $collapses)) {
                $cattpl->collapseclass = 'collapsed';
            } else {
                $cattpl->collapseclass = '';
            }

            if (!empty($cattpl->collapseclass)) {
                $cattpl->collapseiconurl = $OUTPUT->image_url('collapsed', 'local_my');
            } else {
                $cattpl->collapseiconurl = $OUTPUT->image_url('expanded', 'local_my');
            }

            if ($cat->category->visible || has_capability('moodle/category:viewhiddencategories', $catcontext)) {

                $cattpl->catstyle = ($cat->category->visible) ? '' : 'dimmed';

                if ($options['withcats'] == 1) {
                    $cattpl->catname = format_string($cat->category->name);
                } else if ($options['withcats'] > 1) {
                    $cats = array();
                    $cats[] = format_string($cat->category->name);
                    if ($cat->category->parent) {
                        $parent = $cat->category;
                        for ($i = 1; $i < $options['withcats']; $i++) {
                            $parent = $DB->get_record('course_categories', array('id' => $parent->parent));
                            $cats[] = format_string($parent->name);
                        }
                    }
                    $cats = array_reverse($cats);
                    $cattpl->catname = implode(' / ', $cats);
                }

                $cattpl->courses = array();
                foreach ($cat->courses as $c) {
                    $coursetpl = new StdClass;
                    $coursecontext = context_course::instance($c->id);
                    if ($c->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                        $coursetpl->courseurl = new moodle_url('/course/view.php', array('id' => $c->id));
                        $coursetpl->cstyle = ($c->visible && empty($catstyle)) ? '' : 'dimmed';
                        $coursetpl->fullname = format_string($c->fullname);
                        $coursetpl->editingicon = $renderer->editing_icon($c);
                        $cattpl->courses[] = $coursetpl;
                    }
                }

                $template->categories[] = $cattpl;
                $template->hascategories = true;
            }
        }
        return($this->output->render_from_template('local_my/courses_with_categories', $template));
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
