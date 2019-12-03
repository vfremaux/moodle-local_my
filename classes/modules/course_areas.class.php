<?php

namespace local_my\module;

use \StdClass;
use \moodle_url;
use \context_course;

class course_areas_module extends module {

    protected $options;

    protected static $areakey = 'courseareas';
    protected static $areaconfigkey = 'coursearea';

    protected $retainedcategories;
    protected $mastercategory;

    public function __construct() {
        global $DB;

        parent::__construct();
        $this->area = 'course_areas';
        $this->modulename = get_string('courseareas', 'local_my');

        $this->options = array();
        $this->options['withcats'] = self::$config->printcategories;
        $this->options['gaugewidth'] = 60;
        $this->options['gaugeheight'] = 15;

        // Resolve retained categories from the config

        for ($i = 0; $i < self::$config->{self::$areakey}; $i++) {

            // Process each area.
            $key = self::$areaconfigkey.$i;

            if (empty(self::$config->$key)) {
                continue;
            }

            $categoryid = self::$config->$key;

            $this->mastercategory[$key] = $DB->get_record('course_categories', array('id' => $categoryid));
            if (!$this->mastercategory[$key]) {
                continue;
            }

            // Filter courses of this area.
            $this->retainedcategories[$key] = local_get_cat_branch_ids_rec($categoryid);
        }
    }

    public function render($required = 'aslist') {
        global $OUTPUT, $DB, $PAGE, $USER;

        if (empty(self::$config->{self::$areakey})) {
            // Performance quick trap if no areas defined at all.
            return;
        }

        $this->get_courses();

        list($view, $isstudent, $isteacher, $iscoursemanager) = self::resolve_view();

        $template = new StdClass();
        $template->isaccordion = !empty(self::$config->courselistaccordion);

        $colwidth = false;
        if (self::$config->{self::$areakey} % 3 == 0) {
            $colwidth = 33;
        }

        if (!$colwidth) {
            if (self::$config->{self::$areakey} % 2 == 0) {
                $colwidth = 50;
            }
        }

        if (!$colwidth) {
            switch (self::$config->{self::$areakey}) {
                case 1:
                    $colwidth = 100;
                    break;
                case 2:
                    $colwidth = 50;
                    break;
                default:
                    $colwidth = 32;
            }
        }

        $reali = 1;
        for ($i = 0; $i < self::$config->{self::$areakey}; $i++) {

            // Process each area.
            $key = self::$areaconfigkey.$i;

            // Filter courses of this area.
            $retainedcategories = $this->retainedcategories[$key];

            $areacourses = [];
            foreach ($this->courses as $c) {

                $context = context_course::instance($c->id);
                // Treat site admins as standard users.
                // $editing = has_capability('moodle/course:manageactivities', $context, $USER, false);
                $hasteachingactivity = has_capability('local/my:isteacher', $context, $USER, false);
                $hasmanageractivity = has_capability('local/my:iscoursemanager', $context, $USER, false);

                // Filter out non editing.
                if ($view == 'asteacher') {
                    if (!$hasteachingactivity) {
                        self::add_debuginfo("Course {$c->id} excluded because non teaching course and teaching panel", $c->id);
                        continue;
                    }
                } else if ($view == 'ascoursemanager') {
                    if (!$hasmanageractivity) {
                        self::add_debuginfo("Course {$c->id} excluded because non managed course and manager panel", $c->id);
                        continue;
                    }
                } else {
                    if ($hasteachingactivity || $hasmanageractivity) {
                        self::add_debuginfo("Course {$c->id} excluded because managed/teached course and student panel", $c->id);
                        continue;
                    }
                }

                if (is_null($retainedcategories)) {
                    debugging("null retained cat");
                }
                if (in_array($c->category, $retainedcategories)) {
                    $areacourses[$c->id] = $c;
                    self::add_debuginfo("Course Add ({$c->id} in course area $key", $c->id);
                    module::$excludedcourses[] = $c->id;
                    self::add_debuginfo("Course exclude {$c->id} after display in coursearea $key", $c->id);
                }
            }

            if (!empty($areacourses)) {

                $courseareatpl = new StdClass();
                $courseareatpl->required = $required;
                $this->resolve_viewtype($courseareatpl, $areacourses);

                if ($i != 0 && ($i % 3 == 0)) {
                    $courseareatpl->coljump = true;
                }
                $courseareatpl->colwidth = $colwidth;
                $courseareatpl->catname = $this->mastercategory[$key]->name;
                $courseareatpl->i = $reali;

                // Solve a performance issue for people having wide access to courses.
                $courseareatpl->area = self::$areakey.'_'.$i;

                if (!empty($courseareatpl->asflatlist)) {

                    $this->options['gaugetype'] = 'sektor';
                    $this->options['gaugewidth'] = '20';
                    $this->options['gaugeheight'] = '20';

                    // Get a simple, one level list.
                    foreach ($areacourses as $cid => $c) {
                        $coursetpl = $this->export_course_for_template($c);
                        $courseareatpl->courses[] = $coursetpl;
                    }
                } else {
                    // as list.
                    $courseareatpl->isaccordion = !empty(self::$config->courselistaccordion);
                    $this->options['gaugetype'] = 'sektor';
                    $this->options['gaugewidth'] = '20';
                    $this->options['gaugeheight'] = '20';
                    $this->options['withcats'] = true;
                    $this->options['isaccordion'] = $courseareatpl->isaccordion;
                    $result = $this->export_courses_cats_for_template($courseareatpl, $areacourses);
                    $courseareatpl->categories = $result->categories;
                    $courseareatpl->catidlist = $result->catidlist;
                }

                $template->courseareas[] = $courseareatpl;

                $reali++;
            }
        }

        $template->debuginfo = self::get_debuginfo();

        return $OUTPUT->render_from_template('local_my/courseareas_module', $template);
    }

    public function get_courses() {
        global $USER, $DB;

        // Get all courses i am in.
        $this->courses = enrol_get_my_courses('id, shortname, fullname');

        // Ensure we have last access.
        foreach ($this->courses as $id => &$c) {
            $params = array('userid' => $USER->id, 'courseid' => $id);
            $c->lastaccess = $DB->get_field('user_lastaccess', 'timeaccess', $params);
        }
    }
}