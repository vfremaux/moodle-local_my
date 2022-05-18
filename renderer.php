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

// We are being called from within a function so all globals are NOT there.
global $CFG;

require_once($CFG->dirroot.'/local/my/lib.php');
require_once($CFG->dirroot.'/course/renderer.php');

if (is_dir($CFG->dirroot.'/theme/fordson_fel')) {
    require_once($CFG->dirroot.'/theme/fordson_fel/classes/output/core/course_renderer.php');

    class local_my_renderer extends theme_fordson_fel\output\core\course_renderer {
        use local_my_renderer_overrides;

        const COURSECAT_SHOW_COURSES_NONE = 0; /* do not show courses at all */
        const COURSECAT_SHOW_COURSES_COUNT = 5; /* do not show courses but show number of courses next to category name */
        const COURSECAT_SHOW_COURSES_COLLAPSED = 10;
        const COURSECAT_SHOW_COURSES_AUTO = 15; /* will choose between collapsed and expanded automatically */
        const COURSECAT_SHOW_COURSES_EXPANDED = 20;
        const COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT = 30;

        const COURSECAT_TYPE_CATEGORY = 0;
        const COURSECAT_TYPE_COURSE = 1;

        public $basecategoryid;
        public static $favorites;

        public static $jscode = [];
    }
} else {

    class local_my_renderer extends \core_course_renderer {
        use local_my_renderer_overrides;

        const COURSECAT_SHOW_COURSES_NONE = 0; /* do not show courses at all */
        const COURSECAT_SHOW_COURSES_COUNT = 5; /* do not show courses but show number of courses next to category name */
        const COURSECAT_SHOW_COURSES_COLLAPSED = 10;
        const COURSECAT_SHOW_COURSES_AUTO = 15; /* will choose between collapsed and expanded automatically */
        const COURSECAT_SHOW_COURSES_EXPANDED = 20;
        const COURSECAT_SHOW_COURSES_EXPANDED_WITH_CAT = 30;

        const COURSECAT_TYPE_CATEGORY = 0;
        const COURSECAT_TYPE_COURSE = 1;

        public $basecategoryid;
        public static $favorites;

        public static $jscode = [];
    }
}

trait local_my_renderer_overrides {

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

        if (is_dir($CFG->dirroot.'/mod/learningtimecheck')) {
            // Let assume mod/learningtimecheck/xlib.php is already included.
            if (learningtimecheck_course_has_ltc_tracking($course->id)) {
                $ratio = learningtimecheck_get_course_ltc_completion($course->id, $USER->id, $mandatory = true);
            }
        }

        if (!isset($ratio)) {
            // Last strategy when not LTC driven.
            $ratio = round(\core_completion\progress::get_course_progress_percentage($courserec));
        }

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

    public function editing_icon(&$course) {
        $context = context_course::instance($course->id);
        if (has_capability('moodle/course:manageactivities', $context)) {
            $attrs = array('aria-label' => get_string('editing', 'local_my'));
            $pix = $this->output->pix_icon('editing', get_string('editing', 'local_my'), 'local_my', $attrs);
            return '<div class="editing-icon pull-right">'.$pix.'</div>';
        }
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

        if (!empty($config->addcourseindexlink)) {
            $tabname = get_string('courseindex', 'local_my');
            $taburl = new moodle_url('/course/index.php');
            $rows[0][] = new tabobject('courseindex', $taburl, $tabname);
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

    public function add_favorite_icon($courseid, $light = '') {

        $this->init_favorites();

        if (in_array($courseid, self::$favorites)) {
            return '<i class="icon icon-favorite fa fas fa-star" data-course="'.$courseid.'"></i>';
        } else {
            $addstr = get_string('addtofavorites', 'local_my');
            $attrs = ['class' => 'icon add-to-favorites-handle icon-favorite fa fa-star-o far '.$light,
                      'data-course' => $courseid,
                      'data-paste-target' => 'local_my_favorites',
                      'title' => $addstr];
            return html_writer::tag('i', '', $attrs);
        }
    }

    public function init_favorites() {
        global $DB, $USER;

        if (is_null(self::$favorites)) {
            $favoriteids = $DB->get_field('user_preferences', 'value', ['userid' => $USER->id, 'name' => 'local_my_favorite_courses']);
            if (!$favoriteids) {
                // Ensure we will NOT fetch again an unset preferences.
                self::$favorites = [];
            } else {
                self::$favorites = explode(',', $favoriteids);
            }
        }
    }

    public function remove_favorite_icon($courseid, $faicon = 'fa-trash') {
        $deletestr = get_string('removefromfavorites', 'local_my');
        $attrs = ['data-course' => $courseid, 'class' => 'icon remove-from-favorites-handle icon-favorite fa '.$faicon.' fa-fw', 'title' => $deletestr];
        return html_writer::tag('i', '', $attrs);
    }

    /**
     * Renders HTML to display particular course category - list of it's subcategories and courses
     *
     * Invoked from /course/index.php
     *
     * @param int|stdClass|core_course_category $category
     */
    public function course_category($category) {
        global $CFG, $PAGE;

        // Register for deeper calls.
        $this->set_basecategoryid($category->basecategoryid);

        $coursecat = core_course_category::get(is_object($category) ? $category->id : $category);
        $site = get_site();
        $output = '';

        if (can_edit_in_category($coursecat->id)) {
            // Add 'Manage' button if user has permissions to edit this category.
            $managebutton = $this->single_button(new moodle_url('/course/management.php',
                array('categoryid' => $coursecat->id)), get_string('managecourses'), 'get');
            $this->page->set_button($managebutton);
        }
        if (!$coursecat->id) {
            if (core_course_category::count_all() == 1) {
                // There exists only one category in the system, do not display link to it
                $coursecat = core_course_category::get_default();
                $strfulllistofcourses = get_string('fulllistofcourses');
                $this->page->set_title("$site->shortname: $strfulllistofcourses");
            } else {
                $strcategories = get_string('categories');
                $this->page->set_title("$site->shortname: $strcategories");
            }
        } else {
            $title = $site->shortname;
            if (core_course_category::count_all() > 1) {
                $title .= ": ". $coursecat->get_formatted_name();
            }
            $this->page->set_title($title);

            // Print the category selector
            if (core_course_category::count_all() > 1) {
                $output .= html_writer::start_tag('div', array('class' => 'categorypicker'));
                $select = new single_select(new moodle_url('/local/my/categories.php', ['basecategoryid' => $category->basecategoryid]), 'categoryid',
                        local_get_cat_branch_rec($category->basecategoryid), $coursecat->id, null, 'switchcategory');
                $select->set_label(get_string('categories').':');
                $output .= $this->render($select);
                $output .= html_writer::end_tag('div'); // .categorypicker
            }
        }

        // Print current category description
        $chelper = new coursecat_helper();
        if ($description = $chelper->get_category_formatted_description($coursecat)) {
            $output .= $this->box($description, array('class' => 'generalbox info'));
        }

        // Prepare parameters for courses and categories lists in the tree
        $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_AUTO)
                ->set_attributes(array('class' => 'category-browse category-browse-'.$coursecat->id));

        $coursedisplayoptions = array();
        $catdisplayoptions = array();
        $browse = optional_param('browse', null, PARAM_ALPHA);
        $perpage = optional_param('perpage', $CFG->coursesperpage, PARAM_INT);
        $page = optional_param('page', 0, PARAM_INT);
        $baseurl = new moodle_url('/local/my/categories.php');
        if ($coursecat->id) {
            $baseurl->param('categoryid', $coursecat->id);
        }
        if ($perpage != $CFG->coursesperpage) {
            $baseurl->param('perpage', $perpage);
        }
        $coursedisplayoptions['limit'] = $perpage;
        $catdisplayoptions['limit'] = $perpage;
        if ($browse === 'courses' || !$coursecat->has_children()) {
            $coursedisplayoptions['offset'] = $page * $perpage;
            $coursedisplayoptions['paginationurl'] = new moodle_url($baseurl, array('browse' => 'courses'));
            $catdisplayoptions['nodisplay'] = true;
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'categories'));
            $catdisplayoptions['viewmoretext'] = new lang_string('viewallsubcategories');
        } else if ($browse === 'categories' || !$coursecat->has_courses()) {
            $coursedisplayoptions['nodisplay'] = true;
            $catdisplayoptions['offset'] = $page * $perpage;
            $catdisplayoptions['paginationurl'] = new moodle_url($baseurl, array('browse' => 'categories'));
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'courses'));
            $coursedisplayoptions['viewmoretext'] = new lang_string('viewallcourses');
        } else {
            // we have a category that has both subcategories and courses, display pagination separately
            $coursedisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'courses', 'page' => 1));
            $catdisplayoptions['viewmoreurl'] = new moodle_url($baseurl, array('browse' => 'categories', 'page' => 1));
        }
        $chelper->set_courses_display_options($coursedisplayoptions)->set_categories_display_options($catdisplayoptions);
        // Add course search form.
        // $output .= $this->course_search_form();

        // Display course category tree.
        $output .= $this->coursecat_tree($chelper, $coursecat);

        // $output .= $this->container_end();

        return $output;
    }

    /**
     * Returns HTML to display the subcategories and courses in the given category
     *
     * This method is re-used by AJAX to expand content of not loaded category
     *
     * @param coursecat_helper $chelper various display options
     * @param coursecat $coursecat
     * @param int $depth depth of the category in the current tree
     * @return string
     */
    protected function coursecat_category(coursecat_helper $chelper, $coursecat, $depth) {
        // open category tag
        $classes = array('category');
        if (empty($coursecat->visible)) {
            $classes[] = 'dimmed_category';
        }
        if ($chelper->get_subcat_depth() > 0 && $depth >= $chelper->get_subcat_depth()) {
            // do not load content
            $categorycontent = '';
            $classes[] = 'notloaded';
            if ($coursecat->get_children_count() ||
                    ($chelper->get_show_courses() >= self::COURSECAT_SHOW_COURSES_COLLAPSED && $coursecat->get_courses_count())) {
                $classes[] = 'with_children';
                $classes[] = 'collapsed';
            }
        } else {
            // load category content
            $categorycontent = $this->coursecat_category_content($chelper, $coursecat, $depth);
            $classes[] = 'loaded';
            if (!empty($categorycontent)) {
                $classes[] = 'with_children';
                // Category content loaded with children.
                $this->categoryexpandedonload = true;
            }
        }

        // Make sure JS file to expand category content is included.
        $this->coursecat_include_js();

        $content = html_writer::start_tag('div', array(
            'class' => join(' ', $classes),
            'data-categoryid' => $coursecat->id,
            'data-depth' => $depth,
            'data-showcourses' => $chelper->get_show_courses(),
            'data-type' => self::COURSECAT_TYPE_CATEGORY,
        ));

        // category name
        $categoryname = $coursecat->get_formatted_name();
        $categoryname = html_writer::link(new moodle_url('/local/my/categories.php',
                array(
                    'categoryid' => $coursecat->id,
                    'basecategoryid' => $this->basecategoryid
                )),
                $categoryname);
        if ($chelper->get_show_courses() == self::COURSECAT_SHOW_COURSES_COUNT
                && ($coursescount = $coursecat->get_courses_count())) {
            $categoryname .= html_writer::tag('span', ' ('. $coursescount.')',
                    array('title' => get_string('numberofcourses'), 'class' => 'numberofcourse'));
        }
        $content .= html_writer::start_tag('div', array('class' => 'info'));

        $content .= html_writer::tag(($depth > 1) ? 'h4' : 'h3', $categoryname, array('class' => 'categoryname'));
        $content .= html_writer::end_tag('div'); // .info

        // add category content to the output
        $content .= html_writer::tag('div', $categorycontent, array('class' => 'content'));

        $content .= html_writer::end_tag('div'); // .category

        // Return the course category tree HTML
        return $content;
    }

    /**
     * Renders the list of subcategories in a category
     *
     * @param coursecat_helper $chelper various display options
     * @param core_course_category $coursecat
     * @param int $depth depth of the category in the current tree
     * @return string
     */
    protected function coursecat_subcategories(coursecat_helper $chelper, $coursecat, $depth) {
        global $CFG;
        $subcategories = array();
        if (!$chelper->get_categories_display_option('nodisplay')) {
            $subcategories = $coursecat->get_children($chelper->get_categories_display_options());
        }
        $totalcount = $coursecat->get_children_count();
        if (!$totalcount) {
            // Note that we call core_course_category::get_children_count() AFTER core_course_category::get_children()
            // to avoid extra DB requests.
            // Categories count is cached during children categories retrieval.
            return '';
        }

        // prepare content of paging bar or more link if it is needed
        $paginationurl = $chelper->get_categories_display_option('paginationurl');
        $paginationallowall = $chelper->get_categories_display_option('paginationallowall');
        if ($totalcount > count($subcategories)) {
            if ($paginationurl) {
                $paginationurl->param('basecategoryid', $this->basecategoryid);
                // the option 'paginationurl was specified, display pagingbar
                $perpage = $chelper->get_categories_display_option('limit', $CFG->coursesperpage);
                $page = $chelper->get_categories_display_option('offset') / $perpage;
                $pagingbar = $this->paging_bar($totalcount, $page, $perpage,
                        $paginationurl->out(false, array('perpage' => $perpage)));
                if ($paginationallowall) {
                    $pagingbar .= html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => 'all')),
                            get_string('showall', '', $totalcount)), array('class' => 'paging paging-showall'));
                }
            } else if ($viewmoreurl = $chelper->get_categories_display_option('viewmoreurl')) {
                // the option 'viewmoreurl' was specified, display more link (if it is link to category view page, add category id)
                if ($viewmoreurl->compare(new moodle_url('/local/my/categories.php'), URL_MATCH_BASE)) {
                    $viewmoreurl->params(['categoryid', $coursecat->id, 'basecategoryid' => $this->basecategoryid]);
                }
                $viewmoretext = $chelper->get_categories_display_option('viewmoretext', new lang_string('viewmore'));
                $morelink = html_writer::tag('div', html_writer::link($viewmoreurl, $viewmoretext),
                        array('class' => 'paging paging-morelink'));
            }
        } else if (($totalcount > $CFG->coursesperpage) && $paginationurl && $paginationallowall) {
            // there are more than one page of results and we are in 'view all' mode, suggest to go back to paginated view mode
            $pagingbar = html_writer::tag('div', html_writer::link($paginationurl->out(false, array('perpage' => $CFG->coursesperpage)),
                get_string('showperpage', '', $CFG->coursesperpage)), array('class' => 'paging paging-showperpage'));
        }

        // display list of subcategories
        $content = html_writer::start_tag('div', array('class' => 'subcategories'));

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }

        foreach ($subcategories as $subcategory) {
            $content .= $this->coursecat_category($chelper, $subcategory, $depth + 1);
        }

        if (!empty($pagingbar)) {
            $content .= $pagingbar;
        }
        if (!empty($morelink)) {
            $content .= $morelink;
        }

        $content .= html_writer::end_tag('div');
        return $content;
    }

    /**
     * Serves requests to /course/category.ajax.php
     *
     * In this renderer implementation it may expand the category content or
     * course content.
     *
     * @return string
     * @throws coding_exception
     */
    public function coursecat_ajax() {
        global $DB, $CFG;

        $type = required_param('type', PARAM_INT);

        if ($type === self::COURSECAT_TYPE_CATEGORY) {
            // This is a request for a category list of some kind.
            $categoryid = required_param('categoryid', PARAM_INT);
            $showcourses = required_param('showcourses', PARAM_INT);
            $depth = required_param('depth', PARAM_INT);

            $category = core_course_category::get($categoryid);

            $chelper = new coursecat_helper();
            $params = [
                'categoryid' => $categoryid,
                'basecategoryid' => $this->basecategoryid,
            ];
            $baseurl = new moodle_url('/local/my/categories.php', $params);
            $coursedisplayoptions = array(
                'limit' => $CFG->coursesperpage,
                'viewmoreurl' => new moodle_url($baseurl, array('browse' => 'courses', 'page' => 1))
            );
            $catdisplayoptions = array(
                'limit' => $CFG->coursesperpage,
                'viewmoreurl' => new moodle_url($baseurl, array('browse' => 'categories', 'page' => 1))
            );
            $chelper->set_show_courses($showcourses)->
                    set_courses_display_options($coursedisplayoptions)->
                    set_categories_display_options($catdisplayoptions);

            return $this->coursecat_category_content($chelper, $category, $depth);
        } else if ($type === self::COURSECAT_TYPE_COURSE) {
            // This is a request for the course information.
            $courseid = required_param('courseid', PARAM_INT);

            $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

            $chelper = new coursecat_helper();
            $chelper->set_show_courses(self::COURSECAT_SHOW_COURSES_EXPANDED);
            return $this->coursecat_coursebox_content($chelper, $course);
        } else {
            throw new coding_exception('Invalid request type');
        }
    }

    public function set_basecategoryid($coursecatid) {
        global $SESSION;
        $SESSION->basecategoryid = $coursecatid;
        $this->basecategoryid = $coursecatid;
    }

    public function render_ajax_widget($uid, $widgetclass) {
        global $CFG;

        // Get sort and filter values.

        // Get class for widget.

        $display = optional_param('display', '', PARAM_TEXT);
        if ($display == 'displaylist' || $display == 'displaysummary') {
            // Fallback to standard list course widget.
            $widgetclass = str_replace('_grid', '', $widgetclass);
            $widgetclass = str_replace('_slider', '', $widgetclass);
        }

        $classname = $widgetclass.'_module';
        $fqclassname = '\\local_my\\module\\'.$widgetclass.'_module';
        debug_trace("Rendering ".$widgetclass." by ajax for user $uid ");
        include_once($CFG->dirroot.'/local/my/classes/modules/'.$widgetclass.'.class.php');
        $instance = new $fqclassname();
        $instance->set_uid($uid); // Ensures we keep the original uid.
        $instance->set_option('sort', optional_param('sort', '', PARAM_TEXT));
        $instance->set_option('display', optional_param('display', '', PARAM_TEXT));
        $instance->set_option('schedule', optional_param('schedule', '', PARAM_TEXT));

        // Render.

        return $instance->render();

    }

    public function render_js_code($outcode) {
        if ($outcode) {
            $str = '<script type="text/javascript" >'."\n";
        } else {
            $str = '';
        }
        $str .= implode("\n", self::$jscode);
        if ($outcode) {
            $str .= '</script>';
        }

        return $str;
    }

    /**
     * Renders an explicit printable expression of the filtering values of the course filter.
     */
    public function render_filter_states($uid, $widget) {

        $config = get_config('local_my');
        $states = local_my_get_filter_states($uid, $widget);

        $str = get_string('youaredisplaying', 'local_my').': ';
        foreach ($states as $statekey => $statevalue) {
            if ($statevalue == 'undefined') {
                continue;
            }
            if ($statevalue == '*') {
                // For better string resolution.
                $statevalue = 'all';
            }
            $str .= '<span class="filter-state filter-'.$statekey.'">'.get_string($statevalue, 'local_my').'</span> ';
        }

        return $str;
    }

    /**
     * This function creates a minimal JS script that requires and calls a single function from an AMD module with arguments.
     * If it is called multiple times, it will be executed multiple times.
     *
     * @param string $fullmodule The format for module names is <component name>/<module name>.
     * @param string $func The function from the module to call
     * @param array $params The params to pass to the function. They will be json encoded, so no nasty classes/types please.
     */
    public function js_call_amd($fullmodule, $func, $params = array()) {
        global $CFG;

        list($component, $module) = explode('/', $fullmodule, 2);

        $component = clean_param($component, PARAM_COMPONENT);
        $module = clean_param($module, PARAM_ALPHANUMEXT);
        $func = clean_param($func, PARAM_ALPHANUMEXT);

        $jsonparams = array();
        foreach ($params as $param) {
            $jsonparams[] = json_encode($param);
        }
        $strparams = implode(', ', $jsonparams);
        if ($CFG->debugdeveloper) {
            $toomanyparamslimit = 2048;
            if (strlen($strparams) > $toomanyparamslimit) {
                debugging('Too much data passed as arguments to js_call_amd("' . $fullmodule . '", "' . $func .
                        '"). Generally there are better ways to pass lots of data from PHP to JavaScript, for example via Ajax, data attributes, ... . ' .
                        'This warning is triggered if the argument string becomes longer than ' . $toomanyparamslimit . ' characters.', DEBUG_DEVELOPER);
            }
        }

        $js = 'require(["' . $component . '/' . $module . '"], function(amd) { amd.' . $func . '(' . $strparams . '); });';

        self::$jscode[] = $js;
    }

    public function is_favorite($courseid) {

        $this->init_favorites();

        return in_array($courseid, self::$favorites);
    }
}
