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

require_once($CFG->dirroot.'/local/my/lib.php');
require_once($CFG->dirroot.'/local/my/classes/modules/my_authored_courses.class.php');
require_once($CFG->dirroot.'/local/my/classes/modules/my_managed_courses.class.php');

use \StdClass;
use \moodle_url;
use \context_course;
use \context_coursecat;
use \context_system;
use \coding_exception;
use \completion_info;
use \Collator;

/**
 * A module is a renderable object that drives a widget. Abstract to avoid direct instanciation.
 */
abstract class module {

    /**
     * List of excluded course ids
     */
    public static $excludedcourses;

    /**
     * List of course areas ids
     */
    public static $courseareascourses;

    /**
     * courseareas structure by name
     */
    protected static $courseareas;

    protected static $config;

    protected static $debuginfo = '';

    protected static $debug;

    protected static $renderer;

    protected static $isslickrendered = false;

    /**
     * Marks each panel type state in the current view.
     */
    public static $isstudent;
    public static $isteacher;
    public static $iscoursemanager;
    public static $isadmin;

    /**
     * Marks when the view has been resolved for the widget
     */
    public static $isresolved;

    /**
     * the current view. Obtained by resolve_view().
     * Valid when isresolved is true.
     */
    protected static $view;

    /**
     * Lists all modules used in the current view.
     */
    protected static $modules;

    /**
     * Lists all modules used in the current view in left column when column splitting is used.
     */
    protected static $leftmodules;

    /**
     * Lists all modules used in the current view in right column when column splitting is used.
     */
    protected static $rightmodules;

    /**
     * Lists all modules used in all viewes.
     */
    protected static $allmodules;

    protected $courses;

    protected $area;

    protected $modulename;

    protected $buttons;

    /**
     * Unique id for ajax target identification.
     */
    protected $uid;

    /**
     * A set of rendering options.
     */
    protected $options;

    public function __construct() {
        static $uidseed = 0;

        if (!isset(self::$isresolved)) {
            self::static_init();
        }

        $this->courses = [];
        $this->options = [];
        $this->buttons = '';
        $uidseed++;
        $this->uid = $uidseed;
    }

    public static function static_init() {
        global $PAGE;

        self::$isstudent = null;
        self::$isteacher = null;
        self::$iscoursemanager = null;
        self::$isadmin = null;
        self::$isresolved = false;

        if (is_null(self::$config)) {
            self::$config = get_config('local_my');
        }

        if (is_null(self::$excludedcourses)) {
            self::$excludedcourses = [];
            if (!empty(self::$config->excludedcourses)) {
                self::$excludedcourses = explode(',', self::$config->excludedcourses);
                foreach (self::$excludedcourses as &$cid) {
                    $cid = trim($cid);
                    self::add_debuginfo("Course exclude : (Initial exclude $cid)", $cid);
                }
            }
        }

        if (is_null(self::$courseareascourses)) {
            self::$courseareascourses = [];
            self::$courseareas = [];
        }

        self::$renderer = $PAGE->get_renderer('local_my');

        self::$debug = optional_param('showresolve', false, PARAM_INT);
        self::$debuginfo = '';
    }

    public function set_uid($uid) {
        $this->uid = $uid;
    }

    abstract function render($required = 'aslist');

    abstract function get_courses();

    public function get_courses_internal() {
        return $this->courses;
    }

    public function remove_course($cid) {
        unset($this->courses[$cid]);
    }

    /**
     * Prefetches and caches the course list in a course area.
     * @param string $courseareaname the name of the course area
     * @param arrayref $allmycourses
     */
    protected static function get_coursearea_courses($courseareaname, &$allmycourses, $ids = false) {
        global $DB;

        if (!array_key_exists($courseareaname, self::$courseareas)) {

            if (!empty(self::$config->$courseareaname)) {

                self::$courseareas[$courseareaname] = array();
                $mastercategory = $DB->get_record('course_categories', array('id' => self::$config->$courseareaname));
                if ($mastercategory) {
                    // Filter courses of this area.
                    $retainedcategories = local_get_cat_branch_ids_rec($mastercategory->id);
                    foreach ($allmycourses as $c) {
                        if (in_array($c->category, $retainedcategories)) {
                            $c->summary = $DB->get_field('course', 'summary', array('id' => $c->id));
                            self::$courseareas[$courseareaname][$c->id] = $c;
                            self::add_debuginfo("get courses for course area : add course ".$c->id);
                        } else {
                            self::add_debuginfo("get courses for course area : reject course ".$c->id);
                        }
                    }
                }
            } else {
                self::$courseareas[$courseareaname] = array();
            }
        }

        if ($ids) {
            return array_keys(self::$courseareas[$courseareaname]);
        }
        return self::$courseareas[$courseareaname];
    }


    protected function get_buttons() {
        return '';
    }

    public static function get_renderer() {
        return self::$renderer;
    }

    public function export_courses() {
        return $this->courses;
    }

    public static function get_all_used_modules() {

        $modaskeys = [];
        $configs = ['teachermodules', 'coursemanagermodules', 'adminmodules', 'modules'];

        foreach ($configs as $c) {
            $modules = preg_split("/[\\n,]|\\s+/", self::$config->$c);
            foreach ($modules as $m) {
                $m = trim($m);
                $modaskeys[$m] = 1;
            }
        }
        self::$allmodules = array_keys($modaskeys);
    }

    /**
     * Fetches modules to chow on the current view
     * @param string $view
     */
    public static function fetch_modules($view) {
        global $CFG, $OUTPUT;

        self::$modules = []; // Modules on the current view.
        self::$leftmodules = []; // Modules of the current view pushed at left column.
        self::$allmodules = []; // All used modules, on any view.

        // Get the whole list of used modules.
        self::get_all_used_modules();

        switch ($view) {
            case 'asteacher';
                $modgroup = 'teachermodules';
                break;

            case 'ascoursemanager':
                $modgroup = 'coursemanagermodules';
                break;

            case 'asadmin':
                $modgroup = 'adminmodules';
                break;

            default:
                $modgroup = 'modules';
        }

        if (self::$config->$modgroup) {

            $modules = preg_split("/[\\n,]|\\s+/", self::$config->$modgroup);

            foreach ($modules as $module) {

                $module = trim($module);

                if (empty($module)) {
                    // Avoid blank lines.
                    continue;
                }

                if (strpos($module, '#') === 0 || strpos($module, '/') === 0) {
                    // Skip commented lines.
                    continue;
                }

                // Extract column info.
                $isleft = true;
                if (preg_match('/(.*)-L$/', $module, $matches)) {
                    $isleft = true;
                    $module = $matches[1];
                }

                // Backcompatibility fixes
                $module = preg_replace('/^teacher/', 'my_teacher', $module);
                $module = preg_replace('/^authored/', 'my_authored', $module);
                $module = preg_replace('/^managed/', 'my_managed', $module);
                $module = preg_replace('/^latestnews/', 'latest_news', $module);

                $modulekey = $module;
                if (preg_match('/^static/', $module)) {
                    $module = 'statictext';
                }

                if (preg_match('/^block/', $module)) {
                    list($module, $unused) = explode('_', $module);
                }

                if (!is_file($CFG->dirroot.'/local/my/classes/modules/'.$module.'.class.php')) {
                    include_once($CFG->dirroot.'/local/my/classes/modules/notavailable.class.php');
                    $missingmodule = new \local_my\module\notavailable_module();
                    $missingmodule->set_required($module);
                    if ($isleft) {
                        self::$leftmodules[$modulekey] = $missingmodule;
                    } else {
                        self::$rightmodules[$modulekey] = $missingmodule;
                    }
                    self::$modules[$modulekey] = $missingmodule;
                } else {
                    // May we need this some time to see the upper called module class.
                    // debug_trace("Loading ".$CFG->dirroot.'/local/my/classes/modules/'.$module.'.class.php');
                }
                include_once($CFG->dirroot.'/local/my/classes/modules/'.$module.'.class.php');
                if ($isleft) {
                    $modclassfunc = '\\local_my\\module\\'.$module.'_module';
                    $instance = new $modclassfunc($modulekey);
                    self::$leftmodules[$modulekey] = $instance;
                } else {
                    // In case it has been explicitely right-located (default).
                    $modclassfunc = '\\local_my\\module\\'.$module.'_module';
                    $instance = new $modclassfunc($modulekey);
                    self::$rightmodules[$modulekey] = $instance;
                }
                // debug_trace("All modules loaded");

                self::$modules[$modulekey] = $instance;
            }
        }
    }

    public static function resolve_view() {

        $studentcap = 'local/my:isstudent';
        $teachercap = 'local/my:isteacher';
        $authorcap = 'local/my:isauthor';
        $coursemanagercap = 'local/my:iscoursemanager';

        if (is_null(self::$isstudent)) {
            self::$isstudent = local_my_has_capability_somewhere($studentcap, true, true, false, CONTEXT_COURSE);
        }

        if (is_null(self::$isteacher)) {
            self::$isteacher = (local_my_has_capability_somewhere($teachercap) ||
                    local_my_has_capability_somewhere($authorcap, true, true, false, CONTEXT_COURSECAT)) &&
                            !local_my_is_panel_empty('teachermodules');
        }

        if (is_null(self::$iscoursemanager) &&
                !empty(self::$config->coursemanagermodules) &&
                        preg_match('/\bmanaged/', self::$config->coursemanagermodules)) {
            self::$iscoursemanager = local_my_has_capability_somewhere($coursemanagercap, true, true, false) &&
                    !local_my_is_panel_empty('coursemanagermodules');
        }

        if (is_null(self::$isadmin) && !local_my_is_panel_empty('adminmodules')) {
            $systemcontext = context_system::instance();
            self::$isadmin = has_capability("moodle/site:config", $systemcontext) || has_capability("local/my:ismanager", $systemcontext);
        }

        $view = optional_param('view', '', PARAM_TEXT);
        if (empty($view)) {

            $view = 'asstudent';
            if (self::$isteacher && !empty(self::$config->teachermodules)) {
                // Defaults for teachers.
                $view = 'asteacher';
            }
            if (self::$iscoursemanager && !empty(self::$config->coursemanagermodules)) {
                // Defaults for coursemanagers.
                $view = 'ascoursemanager';
            }
            if (self::$isadmin && !empty(self::$config->adminmodules)) {
                $view = 'asadmin';
            }
        }

        self::$view = $view;
        self::$isresolved = true;

        $result = array($view, self::$isstudent, self::$isteacher, self::$iscoursemanager, self::$isadmin);
        return $result;
    }

    public static function pre_process_exclusions($view) {

        // First pre process course areas

        self::prefetch_course_areas();
        if (!empty(self::$courseareascourses)) {
            $courseareaskeys = array_keys(self::$courseareascourses);
            local_my_scalar_array_merge(self::$excludedcourses, $courseareaskeys);

            foreach ($courseareaskeys as $cid) {
                self::add_debuginfo("Course remove : (coursearea prefetch $cid)", $cid);
            }
        }

        // then exclude some other courses that should be printed on other views

        if ($view == 'asstudent' && self::$isteacher) {
            // If i am teacher and viewing the student tab, prefech teacher courses to exclude them.

            $authormodule = new my_authored_courses_module();
            $prefetchcourses = $authormodule->get_courses();
            if (is_array($prefetchcourses)) {
                $prefetchkeys = array_keys($prefetchcourses);
            } else {
                $prefetchkeys = [];
            }
            local_my_scalar_array_merge(self::$excludedcourses, $prefetchkeys);
        }

        if (($view == 'asstudent' || $view == 'asteacher') && self::$iscoursemanager) {
            // If i am teacher and viewing the student tab, prefech teacher courses to exclude them.
            $mymanagedmodule = new my_managed_courses_module();
            $prefetchcourses = $mymanagedmodule->get_courses();
            $prefetchkeys = array_keys($prefetchcourses);
            local_my_scalar_array_merge(self::$excludedcourses, $prefetchkeys);
        }
    }

    /**
     * Register all course ids that are captured by topic driven courseareas. This ids wil
     * Not be displayed on other widgets.
     */
    protected static function prefetch_course_areas() {
        global $DB;

        if (empty(self::$config)) {
            throw new coding_exception("Trying to call prefetch function when module is not statically initialized");
        }

        if (empty(self::$config->courseareas) && empty(self::$config->courseareas2)) {
            // Performance quick trap.
            return [];
        }

        if (empty(self::$modules)) {
            return [];
        }

        $allmycourses = enrol_get_my_courses('id, shortname');

        if (!empty(self::$excludedcourses)) {
            foreach (self::$excludedcourses as $id => $c) {
                unset($allmycourses[$id]);
            }
        }

        self::add_debuginfo("prefetch all courseareas");
        $prefetchareacourses = [];
        // Get the first coursearea zone exclusions.
        if (in_array('course_areas', self::$allmodules) || in_array('course_areas_and_availables', self::$allmodules)) {
            self::add_debuginfo("prefetch courseareas");

            for ($i = 0; $i < self::$config->courseareas; $i++) {
                $courseareakey = 'coursearea'.$i;
                self::add_debuginfo("coursearea get $courseareakey courses in category ".self::$config->$courseareakey);
                $areacourses = self::get_coursearea_courses($courseareakey, $allmycourses, true);
                if (!empty($areacourses)) {
                    foreach ($areacourses as $cid) {
                        if (!in_array($cid, self::$courseareascourses)) {
                            self::add_debuginfo("coursearea Adding course $cid");
                            self::$courseareascourses[] = $cid;
                        }
                    }
                }
            }
        }

        // Add the second coursearea zone exclusions.
        if (in_array('course_areas2', self::$allmodules) || in_array('course_areas2_and_availables', self::$allmodules)) {

            self::add_debuginfo("prefetch courseareas 2");
            for ($i = 0; $i < self::$config->courseareas2; $i++) {
                $courseareakey = 'coursearea2_'.$i;
                self::add_debuginfo("coursearea get $courseareakey courses in category ".self::$config->$courseareakey);
                $areacourses = self::get_coursearea_courses($courseareakey, $allmycourses, true);
                if (!empty($areacourses)) {
                    foreach ($areacourses as $cid) {
                        if (!in_array($cid, self::$courseareascourses)) {
                            self::add_debuginfo("coursearea2 Adding course $cid");
                            self::$courseareascourses[] = $cid;
                        }
                    }
                }
            }
        }
        self::add_debuginfo("Course area list : ". implode(',', self::$courseareascourses));
    }

    /**
     * Given the ocurse list, and the current view, filter courses that are not
     * matching the panel capability.
     */
    public function process_role_filtering() {
        global $USER;

        if (!self::$isresolved) {
            throw new coding_exception("View is not yet available in the widget. resolve_view() should have been called before.");
        }

        // then exclude some other courses that should be printed on other views

        switch (self::$view) {
            case 'asstudent': {
                // If i am teacher and viewing the student tab, prefech teacher courses to exclude them.
                $cap = 'local/my:isstudent';
                break;
            }
            case 'asteacher': {
                // If i am teacher and viewing the student tab, prefech teacher courses to exclude them.
                $cap = 'local/my:isteacher';
                break;
            }
            case 'ascoursemanager': {
                // If i am teacher and viewing the student tab, prefech teacher courses to exclude them.
                $cap = 'local/my:iscoursemanager';
                break;
            }
        }

        if (!empty($this->courses)) {
            foreach ($this->courses as $cid => $c) {
                $context = context_course::instance($cid);
                if (!(has_capability($cap, $context, $USER->id, false))) {
                    unset($this->courses[$cid]);
                }
            }
        }
    }

    // Excludes courses
    protected function process_excluded() {

        if (!empty(self::$excludedcourses)) {
            foreach (self::$excludedcourses as $cid) {
                if (!empty($cid)) {
                    self::add_debuginfo("Course Remove (rejected $cid as excluded)", $cid);
                    unset($this->courses[$cid]);
                }
            }
        }
    }

    // Excludes courses that will be printed in courseareas.
    protected function process_courseareas() {

        if (!empty(self::$courseareascourses)) {
            foreach (self::$courseareascourses as $cid) {
                if (!empty($cid)) {
                    self::add_debuginfo("Course Remove (rejected $cid as in coursearea)", $cid);
                    unset($this->courses[$cid]);
                }
            }
        }
    }

    /**
     * Excludes what has been printed in the current module.
     * @param string $reason for debug tagging prupose
     */
    protected function exclude_post_display($reason) {
        if (!empty($this->courses)) {
            foreach ($this->courses as $c) {
                self::add_debuginfo("Course Removed : exclude after display $c->id as $reason", $c->id);
                if (!in_array($c->id, self::$excludedcourses)) {
                    self::$excludedcourses[] = $c->id;
                }
            }
        }
    }

    protected function process_metas() {
        global $DB;

        foreach ($this->courses as $id => $c) {
            if (!empty(self::$config->skipmymetas)) {
                if (self::is_meta_for_user($c->id)) {
                    self::add_debuginfo("Course Remove (reject meta $id as meta disabled)", $c->id);
                    unset($this->courses[$id]);
                    continue;
                }
            }
            $this->courses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
        }
    }

    protected static function is_meta_for_user($courseid, $userid = 0) {
        global $DB, $USER;

        if ($userid == 0) {
            $userid = $USER->id;
        }

        $sql = "
            SELECT
                SUM(CASE WHEN e.enrol = 'meta' THEN 1 ELSE 0 END) as metas,
                SUM(CASE WHEN e.enrol <> 'meta' THEN 1 ELSE 0 END) as nonmetas
            FROM
                {enrol} e,
                {user_enrolments} ue
            WHERE
                e.id = ue.enrolid AND
                ue.userid = ? AND
                e.status = 0 AND
                e.courseid = ? AND
                ue.status = 0
        ";
        $metainfo = $DB->get_record_sql($sql, array($userid, $courseid));
        if ($metainfo->metas > 0 && !$metainfo->nonmetas) {
            return true;
        }
        return false;
    }

    /**
     * Determines what layout type the course list will have. Will depend on settings, profile
     * course list size etc. Output is : aslist | asflatlist | asgrid
     *
     * General logic is :
     * - if courselist is too long, switch ot categorized list. (aslist)
     * - if courselist is short and graphic display is allowed && grid is required, switch to grid mode
     * - if courselist is short and graphic not is not allowed for the user or is not required output as flat list.
     *
     * @param objectref &$template template is appended attributes for view type control
     * @param array $courses the courselist being printed
     */
    protected function resolve_viewtype(&$template, $courses = null) {
        global $DB, $USER;

        if (is_null($courses)) {
            $courses = $this->courses;
        }

        if (!is_array($courses)) {
            throw new coding_exception("courses should always be an array");
        }

        $coursecount = count($courses);

        if ($coursecount > self::$config->maxuncategorizedlistsize) {
            $template->aslist = true;
            $template->resolved = 'aslist';
            $template->rule = 'bysize';
            return;
        }

        $mode = 'asflatlist';
        $modetag = 'asflatlist-default';

        if ($template->required == 'asgrid') {
            $mode = 'asgrid';
            $modetag = 'required';
        }

        if ($template->required == 'asslider') {
            $mode = 'asslider';
            $modetag = 'required';
        }

        if (!empty(self::$config->profilefieldforcelistmode)) {
            $forcelistfieldid = self::$config->profilefieldforcelistmode;

            $userforcemodevalue = $DB->get_field('user_info_data', 'data', ['userid' => $USER->id, 'fieldid' => $forcelistfieldid]);
            if (preg_match("/\\b{$userforcemodevalue}\\b/", self::$config->profilefieldforcelistvalues)) {
                // this user has forced mode in list.
                $mode = 'asflatlist';
                $modetag = 'byprofile';
            }
        }

        if (!empty(self::$config->profilefieldforcegraphicmode)) {
            $forcelistfieldid = self::$config->profilefieldforcegraphicmode;
            $userforcemodevalue = $DB->get_field('user_info_data', 'data', ['userid' => $USER->id, 'fieldid' => $forcelistfieldid]);
            if (preg_match("/\\b{$userforcemodevalue}\\b/", self::$config->profilefieldforcegraphicvalues)) {
                // this user has forced mode graphic.
                if ($template->required == 'asslider') {
                    $mode = 'asslider';
                } else {
                    $mode = 'asgrid';
                }
                $modetag = 'byprofile';
            }
        }

        $template->resolved = $mode;
        $template->rule = $modetag;
        $template->$mode = true;
    }

    /**
     * get all the valuable information for displaying a category in any format.
     * @param mixed $courseorid integer ID or course object.
     */
    public function export_course_category_for_template($category, $options) {

        $cattpl = new StdClass;

        $fs = get_file_storage();

        $context = context_coursecat::instance($category->id);
        $systemcontext = context_system::instance();

        $cattpl->hasimage = false;
        $cattpl->id = $category->id;
        if (self::$config->trimmode == 'words') {
            $cattpl->trimtitle = local_my_course_trim_words(format_string($category->name), self::$config->trimlength1);
        } else {
            $cattpl->trimtitle = local_my_course_trim_char(format_string($category->name), self::$config->trimlength1);
        }
        $cattpl->categoryurl = new moodle_url('/local/my/categories.php', ['categoryid' => $category->id, 'basecategoryid' => $category->id]);

        if (empty($options['withicons'])) {
            return $cattpl;
        }

        // Process category icons.

        $files = $fs->get_area_files($context->id, 'coursecat', 'description', 0, 'id', false);
        $file = null;
        if (!empty($files)) {
            foreach ($files as $file) {
                // Take fist image NOT TOO BIG (560px max). TOTO
                assert(1);
            }
        }

        if ($file) {
            $cattpl->imgurl = moodle_url::make_pluginfile_url($file->get_contextid(),
                                                             $file->get_component(),
                                                             $file->get_filearea(),
                                                             null,
                                                             $file->get_filepath(),
                                                             $file->get_filename());
            $cattpl->hasimage = true;
        } else {
            // Get default category image.
            $file = $fs->get_file($systemcontext->id, 'local_my', 'rendererimages', 0, '/', 'categorydefaultimage.jpg');
            if (empty($file)) {
                $file = $fs->get_file($systemcontext->id, 'local_my', 'rendererimages', 0, '/', 'categorydefaultimage.png');
                if (empty($file)) {
                    $file = $fs->get_file($systemcontext->id, 'local_my', 'rendererimages', 0, '/', 'categorydefaultimage.svg');
                    if (empty($file)) {
                        $file = $fs->get_file($systemcontext->id, 'local_my', 'rendererimages', 0, '/', 'categorydefaultimage.gif');
                    }
                }
            }

            if (!empty($file)) {
                $cattpl->imgurl = moodle_url::make_pluginfile_url($file->get_contextid(),
                                                                 $file->get_component(),
                                                                 $file->get_filearea(),
                                                                 $file->get_itemid(),
                                                                 $file->get_filepath(),
                                                                 $file->get_filename());
                $cattpl->hasimage = true;
            }
        }
        return $cattpl;
    }

    /**
     * get all the valuable information for displaying a course in any format.
     * @param mixed $courseorid integer ID or course object.
     */
    public function export_course_for_template($courseorid) {
        global $CFG, $USER, $PAGE, $OUTPUT, $DB;

        $PAGE->requires->js('/local/my/js/sektor/sektor.js');
        $config = get_config('local_my');
        $renderer = $PAGE->get_renderer('local_my');

        if (is_object($courseorid)) {
            $courseid = $courseorid->id;
        } else {
            $courseid = $courseorid;
        }

        $coursetpl = new StdClass;
        $course = get_course($courseid);
        $context = context_course::instance($course->id);

        // Course capabilitites.
        $coursetpl->isstudent = has_capability('local/my:isstudent', $context, $USER->id, false);
        $coursetpl->isteacher = has_capability('local/my:isteacher', $context, $USER->id, false);
        $coursetpl->isauthor = has_capability('local/my:isauthor', $context, $USER->id, false);
        $coursetpl->iscoursemanager = has_capability('local/my:iscoursemanager', $context, $USER->id, false);
        $coursetpl->hasteachingrole = $coursetpl->isauthor || $coursetpl->isteacher || $coursetpl->iscoursemanager;
        $coursetpl->canedit = has_any_capability(['local/my:isauthor', 'local/my:iscoursemanager'], $context, $USER->id, false);

        // Process summary info.
        $coursetpl->shortname = $course->shortname;
        $coursetpl->summary = '';
        if (empty(self::$config->hidedescriptions)) {
            $summary = local_my_strip_html_tags($course->summary);
            if (self::$config->trimmode == 'words') {
                $coursetpl->summary = local_my_course_trim_words($summary, self::$config->trimlength2);
            } else {
                $coursetpl->summary = local_my_course_trim_char($summary, self::$config->trimlength2);
            }
        }

        // Calibrate course name.
        $coursetpl->fullname = format_string($course->fullname);
        if (self::$config->trimmode == 'words') {
            $coursetpl->trimtitle = local_my_course_trim_words(format_string($course->fullname), self::$config->trimlength1);
        } else {
            $coursetpl->trimtitle = local_my_course_trim_char(format_string($course->fullname), self::$config->trimlength1);
        }

        // Using my_favorites widget.
        if (local_my_is_using_favorites() && empty($this->options['nofavorable'])) {
            if (!empty($this->options['isfavorite'])) {
                $coursetpl->favoritectl = $renderer->remove_favorite_icon($course->id);
            } else {
                $coursetpl->favoritectl = $renderer->add_favorite_icon($course->id);
            }
        }

        // Using light favorite taggings.
        if (!empty($config->lightfavorites) && empty($this->options['nofavorable'])) {
            if ($renderer->is_favorite($courseid)) {
                $coursetpl->favoritectl = $renderer->remove_favorite_icon($course->id, 'fas fa-star light');
            } else {
                $coursetpl->favoritectl = $renderer->add_favorite_icon($course->id, 'light');
            }
        }

        // Url.
        $courseurl = new moodle_url('/course/view.php', array('id' => $courseid ));
        $coursetpl->courseurl = ''.$courseurl;
        $coursetpl->id = $course->id;

        // Thumb or viewable image.
        $courseinlist = local_get_course_list($course);
        foreach ($courseinlist->get_course_overviewfiles() as $file) {
            if ($isimage = $file->is_valid_image()) {
                $path = '/'. $file->get_contextid(). '/'. $file->get_component().'/';
                $path .= $file->get_filearea().$file->get_filepath().$file->get_filename();
                $coursetpl->imgurl = ''.file_encode_url("$CFG->wwwroot/pluginfile.php", $path, !$isimage);
                break;
            }
        }
        if (empty($coursetpl->imgurl)) {
            $coursetpl->imgurl = ''.local_my_get_image_url('coursedefaultimage');
        }

        // Get user attributes.
        $coursetpl->hasattributes = false;
        if (local_my_is_visible_course($course)) {
            $coursetpl->hiddenclass = '';
            $coursetpl->hiddenattribute = '';
        } else {
            $coursetpl->hasattributes = true;
            $coursetpl->hiddenattribute = $OUTPUT->pix_icon('hidden', get_string('ishidden', 'local_my'), 'local_my');
            $coursetpl->hiddenclass = 'dimmed';
        }
        if (has_capability('moodle/course:manageactivities', $context, $USER, false)) {
            $coursetpl->hasattributes = true;
            $coursetpl->editingclass = 'can-edit';
            $coursetpl->editingattribute = $OUTPUT->pix_icon('editing', get_string('canedit', 'local_my'), 'local_my');
        } else {
            $coursetpl->editingclass = '';
            $coursetpl->editingattribute = '';
        }
        if (local_my_is_selfenrolable_course($course)) {
            $coursetpl->hasattributes = true;
            $coursetpl->selfenrolclass = 'selfenrol';
            $coursetpl->selfattribute = $OUTPUT->pix_icon('unlocked', get_string('selfenrol', 'local_my'), 'local_my');
        } else {
            $coursetpl->selfenrolclass = '';
            $coursetpl->selfattribute = '';
        }
        if (local_my_is_guestenrolable_course($course)) {
            $coursetpl->hasattributes = true;
            $coursetpl->guestenrolclass = 'guestenrol';
            $coursetpl->guestattribute = $OUTPUT->pix_icon('guest', get_string('guestenrol', 'local_my'), 'local_my');
        } else {
            $coursetpl->guestenrolclass = '';
            $coursetpl->guestattribute = '';
        }
        if ($course->startdate > time()) {
            $coursetpl->hasattributes = true;
            $coursetpl->futureclass = 'future';
            $coursetpl->futureattribute = $OUTPUT->pix_icon('future', get_string('future', 'local_my'), 'local_my');
        } else {
            $coursetpl->futureclass = '';
            $coursetpl->futureattribute = '';
        }

        if (!has_capability('local/my:seecourseattributes', $context)) {
            // Hide all attributes if requested by capability.
            $coursetpl->hasattributes = false;
        }

        if ($course instanceof stdClass) {
            $stdcourse = $course;
            $course = local_get_course_list($course);
        }

        // Get teacher signals
        if (!empty($this->options['withteachersignals'])) {
            $coursetpl->selfenrolclass = (local_my_is_selfenrolable_course($course)) ? 'selfenrol' : '';
            $coursetpl->guestenrolclass = (local_my_is_guestenrolable_course($course)) ? 'guestenrol' : '';
        }

        // Get completion for students.
        $coursetpl->hasprogression = false;
        $coursetpl->caneditclass = '';

        // Teaching roles need predominate, even is student role is set too (bad practice).
        if ($coursetpl->hasteachingrole) {
            if ($coursetpl->canedit) {
                $coursetpl->caneditclass = 'can-edit';
            }

            $enrolled = get_users_by_capability($context, 'local/my:isstudent', 'u.id');
            if (!empty($enrolled)) {
                $coursetpl->enrolled = count($enrolled);
            } else {
                $coursetpl->enrolled = 0;
            }

            if (empty($this->options['noprogress'])) {
                $coursetpl->hasindicators = true;
                $completion = new completion_info($course);
                if ($completion->is_enabled(null)) {
                    $select = "
                        course = :courseid AND
                        timecompleted IS NOT NULL
                    ";

                    $completed = 0 + $DB->count_records_select('course_completions', $select, ['courseid' => $course->id]);
                    $ratio = ($coursetpl->enrolled) ? min(($completed / $coursetpl->enrolled) * 100, 100) : 0;

                    $coursetpl->hasprogression = true;
                    $coursetpl->id = $course->id;
                    $coursetpl->ratio = (int)$ratio;
                    $sektorparams = [
                        'id' => '#sektor-progress-'.$course->id,
                        'angle' => round($ratio * 360 / 200),
                        'size' => 20,
                        // height not used.
                    ];
                    $renderer->js_call_amd('local_my/local_my', 'sektor', [$sektorparams]);
                    $coursetpl->coursecompletionstr = get_string('coursecompletionratio', 'local_my');

                    if ($ratio <= 70) {
                        $coursetpl->completiondirclass = 'upcount';
                        $coursetpl->completedusers = 0 + $completed;
                        $coursetpl->completionusersstr = get_string('completedusers', 'local_my');
                    } else {
                        $coursetpl->completiondirclass = 'downcount';
                        $coursetpl->completedusers = max(0, $coursetpl->enrolled - $completed); // Use max to avoid boundary errors.
                        $coursetpl->completionusersstr = get_string('tocompleteusers', 'local_my');
                    }

                    // Other signals. Defer to Pro Decorator.
                    if (local_my_supports_feature('widgets/indicators')) {
                        include_once($CFG->dirroot.'/local/my/pro/classes/moduleadds.class.php');
                        pro_modules_additions::add_teacher_indicators($coursetpl, $course);
                    }
                }
            }
        } else if ($coursetpl->isstudent) {

            // Completion signal.
            if (empty($this->options['noprogress'])) {
                $coursetpl->hasindicators = true;
                $completion = new completion_info($course);
                if ($completion->is_enabled(null)) {
                    $coursetpl->hasprogression = true;

                    $ratio = round(\core_completion\progress::get_course_progress_percentage($stdcourse));

                    $coursetpl->ratio = 0 + $ratio;
                    $coursetpl->coursecompletionstr = get_string('mycoursecompletion', 'local_my');
                    $sektorparams = [
                        'id' => '#sektor-progress-'.$course->id,
                        'angle' => round($ratio * 360 / 100),
                        'size' => 20,
                        // height not used.
                    ];
                    $renderer->js_call_amd('local_my/local_my', 'sektor', [$sektorparams]);
                }

                // Other signals. Defer to Pro Decorator.
                if (local_my_supports_feature('widgets/indicators')) {
                    include_once($CFG->dirroot.'/local/my/pro/classes/moduleadds.class.php');
                    pro_modules_additions::add_student_indicators($coursetpl, $course);
                }
            }
        }

        $coursetpl->heightstyle = 'style="height: '.$config->courseboxheight.' "';

        return $coursetpl;
    }

    /**
     * export courses with first level category caption
     */
    public function export_courses_cats_for_template($template, $courses = null) {
        global $CFG, $DB, $USER, $OUTPUT, $PAGE;

        if (is_null($courses)) {
            $courses = $this->courses;
        }

        // Get user preferences for collapser.
        $select = " userid = ? and name LIKE 'local_my%' ";
        $params = array('userid' => $USER->id);
        $collapses = $DB->get_records_select_menu('user_preferences', $select, $params, 'name,value', 'name,value');

        // Reorganise by cat.
        $catcourses = [];
        foreach ($courses as $c) {
            if (!isset($catcourses[$c->category])) {
                $catcourses[$c->category] = new StdClass;
                $catcourses[$c->category]->category = $DB->get_record('course_categories', array('id' => $c->category));
            }
            $catcourses[$c->category]->courses[] = $c;
        }
        $catcourses[$c->category]->totalofcourses = count($catcourses[$c->category]->courses);

        $output = new Stdclass;
        $output->catidlist = implode(',', array_keys($catcourses));
        $output->categories = [];

        foreach ($catcourses as $catid => $cat) {

            if (!$catid) {
                continue;
            }

            $template->hascategories = true;

            $cattpl = new Stdclass;
            $cattpl->catid = $cat->category->id;

            $catcontext = context_coursecat::instance($catid);
            $cattpl->collapseclass = '';
            if (array_key_exists('local_my_'.$template->area.'_'.$catid.'_hidden', $collapses) && !$this->options['isaccordion']) {
                $cattpl->collapseclass = 'collapsed';
            }

            if (!empty($cattpl->collapseclass)) {
                $cattpl->collapseiconurl = $OUTPUT->image_url('collapsed', 'local_my');
                $cattpl->ariaexpanded = 'false';
            } else {
                $cattpl->collapseiconurl = $OUTPUT->image_url('expanded', 'local_my');
                $cattpl->ariaexpanded = 'true';
            }

            if ($cat->category->visible || has_capability('moodle/category:viewhiddencategories', $catcontext)) {

                $cattpl->catstyle = ($cat->category->visible) ? '' : 'dimmed';

                if (!empty($this->options['withcats']) && ($this->options['withcats'] == 1)) {
                    $cattpl->catname = format_string($cat->category->name);
                } else if (!empty($this->options['withcats']) && ($this->options['withcats'] > 1)) {
                    $cats = array();
                    $cats[] = format_string($cat->category->name);
                    if ($cat->category->parent) {
                        $parent = $cat->category;
                        for ($i = 1; $i < $this->options['withcats']; $i++) {
                            $parent = $DB->get_record('course_categories', array('id' => $parent->parent));
                            $cats[] = format_string($parent->name);
                        }
                    }
                    $cats = array_reverse($cats);
                    $cattpl->catname = implode(' / ', $cats);
                }

                $cattpl->courses = [];
                $this->options['collapsedclass'] = $cattpl->collapseclass;
                foreach ($cat->courses as $c) {
                    $coursetpl = $this->export_course_for_template($c);
                    $cattpl->courses[] = $coursetpl;
                }

                $output->categories[] = $cattpl;
            }
        }

        return $output;
    }

    public static function render_my_caption() {
        if (array_key_exists('my_caption', self::$modules)) {
            return self::$modules['my_caption']->render();
        }
    }

    public static function render_dashboard() {
        global $OUTPUT;

        $template = new StdClass;
        if (in_array('left_edition_column', self::$modules)) {
            $template->hasleft = true;
            $template->spanclasses = 'span6 col-md-6 col-xs-12';
            if (!empty(self::$leftmodules)) {
                $template->leftmodules = '';
                foreach (self::$leftmodules as $m) {
                    $template->leftmodules .= $m->render();
                }
            }
            $template->rightmodules = '';
            foreach (self::$modules as $m) {
                $template->rightmodules .= $m->render();
            }
        } else {
            $spanclass = 'span12 col-xs-12';
            $template->modules = '';
            foreach (self::$modules as $m) {
                $template->modules .= $m->render();
            }
        }

        return $OUTPUT->render_from_template('local_my/my', $template);
    }

    public static function add_debuginfo($message, $courseid = 0) {
        if (self::$debug) {
            if ($courseid == 0 || (self::$debug == 1) || (self::$debug > 1 && self::$debug == $courseid)) {
                self::$debuginfo .= $message."\n";
            }
        }
    }

    public static function get_debuginfo() {
        return self::$debuginfo;
    }

    public static function get_config($key) {
        if (!isset(self::$config->{$key})) {
            throw new coding_exception("Key $key not found in local my config");
        }
        return self::$config->{$key};
    }

    public function set_option($key, $value) {
        $this->options[$key] = $value;
    }

    protected function fix_courses_attributes_for_sorting() {
        global $USER, $DB;

        // Quick perf trap.
        if (empty($this->options['sort'])) {
            return;
        }

        foreach ($this->courses as &$course) {
            if (!isset($course->enddate) && $this->options['sort'] == 'byenddate') {
                $course->enddate = $DB->get_field('course', 'enddate', ['id' => $course->id]);
            }

            if (!isset($course->lastaccess) && $this->options['sort'] == 'bylastaccess') {
                $params = [
                    'courseid' => $course->id,
                    'userid' => $USER->id
                ];
                $course->lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', $params);
            }

            if (!isset($course->ratio) && $this->options['sort'] == 'bycompletion') {
                $course->ratio = round(\core_completion\progress::get_course_progress_percentage($course));
            }

            // Do not fix fullname. We should have it !
        }
    }

    protected function sort_courses() {
        if (array_key_exists('sort', $this->options)) {
            $func = 'sort'.$this->options['sort'];
            uasort($this->courses, [$this, $func]);
        }
    }

    /**
     * sort by name asc.
     */
    public function sortbyname($a, $b) {
        static $collator;

        if (!isset($collator)) {
            $collator = new Collator(null);
        }

        return $collator->compare($a->fullname, $b->fullname);
    }

    /**
     * sort by enddate asc.
     */
    public function sortbyenddate($a, $b) {
        if ($a->enddate > $b->enddate) return 1;
        if ($a->enddate < $b->enddate) return -1;
        return 0;
    }

    /**
     * sort by completion asc.
     */
    public function sortbycompletion($a, $b) {
        if ($a->ratio > $b->ratio) return 1;
        if ($a->ratio < $b->ratio) return -1;
        return 0;
    }

    /**
     * sort by last access desc.
     */
    public function sortbylastaccess($a, $b) {
        if ($a->lastaccess > $b->lastaccess) return -1;
        if ($a->lastaccess < $b->lastaccess) return 1;
        return 0;
    }

    /**
     * sort by favorite. Defaults to sort by name.
     */
    public function sortbyfavorites($a, $b) {
        if (self::$renderer->is_favorite($a->id) && !self::$renderer->is_favorite($b->id)) {
            return -1;
        }
        if (!self::$renderer->is_favorite($a->id) && self::$renderer->is_favorite($b->id)) {
            return 1;
        }
        return $this->sortbyname($a, $b);
    }

    protected function get_filter_templates($filter) {

        $defaultfilteroption = '*';

        $filtertpl = new StdClass;
        $filtertpl->filtername = $filter->name;
        $filtertpl->filterlabelstr = get_string($filter->name, 'local_my');

        foreach ($filter->options as $option) {
            $opttpl = new StdClass;
            $opttpl->value = $option;
            if ($opttpl->value != '*') {
                $opttpl->optionlabelstr = get_string($option, 'local_my');
            } else {
                $opttpl->optionlabelstr = get_string('everything', 'local_my');
            }
            $opttpl->active = $defaultfilteroption == $option; // At the moment, not bound to user preferences. Next step.
            $opttpl->optionarialabelstr = get_string('ariaviewfilteroption', 'local_my', $opttpl->optionlabelstr);
            $filtertpl->filteroptions[] = $opttpl;
        }

        return $filtertpl;
    }

    public function apply_filters() {
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $filter->apply($this);
            }
        }
    }

    public function catch_filters() {
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $filter->catchvalue();
            }
        }
    }
}