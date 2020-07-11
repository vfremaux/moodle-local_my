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

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot.'/local/my/classes/modules/module.class.php');

use Stdclass;
use context_course;
use html_writer;

class my_courses_module extends module {

    public function __construct() {
        parent::__construct();
        $this->area = 'my_courses';
        $this->modulename = get_string('mycourses', 'local_my');

        // Default gauge settings.
        $this->options = [
            'gaugetype' => self::$config->progressgaugetype,
            'gaugewidth' => self::$config->progressgaugewidth,
            'gaugeheight' => self::$config->progressgaugeheight
        ];
    }

    /**
     * @param string $required required prefered view type
     */
    public function render($required = 'aslist') {
        global $DB, $USER, $OUTPUT, $CFG;

        $this->get_courses();

        $template = new StdClass;

        if (!empty(self::$config->effect_opacity)) {
            $template->withopacityeffect = 'with-opacity-effect';
        }

        if (!empty(self::$config->effect_halo)) {
            $template->withhaloeffect = 'with-halo-effect';
        }

        $template->area = $this->area;
        $template->view = optional_param('view', 'asstudent', PARAM_TEXT);
        $template->uid = $this->uid;
        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            $template->ajax = true;
        }
        $template->modulename = $this->modulename;
        $template->buttons = $this->get_buttons();
        if (!empty($this->extraclasses)) {
            $template->extraclasses = $this->extraclasses;
        }

        if (!empty($this->options['display']) && $this->options['display'] == 'displaylist') {
            // invalidate display for short list.
            $template->shortdisplay = true;
        }

        $template->assignicon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'assign'), 'mod_assign');
        $template->quizicon = $OUTPUT->pix_icon('icon', get_string('pluginname', 'quiz'), 'mod_quiz');
        $template->assigntosubmiticon = $OUTPUT->pix_icon('assignstosubmit', get_string('pendingassignstosubmit', 'local_my'), 'local_my');

        if (!$this->has_content($template)) {
            $template->hascourses = false;
            $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
            return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
        }

        if (!empty(self::$config->withsort)) {
            $template->hassortorfilter = true;
            $template->hassort = true;
            $template->sortoptions = $this->get_course_sort_option_templates();
        }

        if (!empty(self::$config->withtimeselector)) {
            $template->hassortorfilter = true;
            $template->hastimeselector = true;
            $template->timeoptions = $this->get_course_time_option_templates();
        }

        if (!empty(self::$config->withdisplay) && preg_match('/_grid|_slider/', $this->area)) {
            $template->hassortorfilter = true;
            $template->hasdisplay = true;
            $template->displayoptions = $this->get_course_display_option_templates();
        }

        if (!empty($this->filters)) {
            $template->hassortorfilter = true;
            $template->hasfilters = true;
            foreach ($this->filters as $filter) {
                $template->filters[] = $this->get_filter_templates($filter);
            }
        }

        $template->hascourses = true;
        $template->required = $required; // prefered view type.

        $this->resolve_viewtype($template);

        if ($template->resolved != 'aslist') {

            $this->options['withcats'] = false;
            $this->options['noprogress'] = self::$config->progressgaugetype == 'noprogress';
            if (!empty($template->resolved == 'asflatlist')) {
                // With flat list and inline course rows we need to force sektor gauge.
                $this->options['gaugetype'] = 'sektor';
                $this->options['gaugewidth'] = '20';
                $this->options['gaugeheight'] = '20';
            } else {
                $this->options['gaugetype'] = self::$config->progressgaugetype;
                $this->options['gaugewidth'] = self::$config->progressgaugewidth;
                $this->options['gaugeheight'] = self::$config->progressgaugeheight;
            }

            // Get a simple, one level list.
            foreach ($this->courses as $cid => $c) {
                $coursetpl = $this->export_course_for_template($c);
                $template->courses[] = $coursetpl;
            }
            $template->totalofcourses = count($template->courses);
        } else {
            // As categorized list.
            $template->isaccordion = !empty(self::$config->courselistaccordion);
            $this->options['gaugetype'] = 'sektor';
            $this->options['gaugewidth'] = '20';
            $this->options['gaugeheight'] = '20';
            $this->options['withcats'] = true;
            $this->options['isaccordion'] = $template->isaccordion;
            $result = $this->export_courses_cats_for_template($template);
            $template->categories = $result->categories;
            $template->catidlist = $result->catidlist;
        }

        // Process exclusion of what has been displayed.
        if (empty($this->options['noexcludefromstream'])) {
            $this->exclude_post_display($this->area);
        }

        $template->debuginfo = self::get_debuginfo();

        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            // Real original module when reloading.
            return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
        } else {
            return $OUTPUT->render_from_template('local_my/my_courses_module-loading_placeholder', $template);
        }
    }

    public function get_courses() {
        global $USER, $DB, $CFG;

        $this->courses = enrol_get_my_courses('id, shortname, fullname');
        foreach (array_keys($this->courses) as $cid) {
            $context = context_course::instance($cid);
            if (!has_capability('local/my:isstudent', $context, $USER->id, false)) {
                // Exclude courses where i'm NOT student.
                self::add_debuginfo("Course Exclude (course $cid not student inside)", $cid);
                unset($this->courses[$cid]);
            } else {
                self::add_debuginfo("Course Add (course $cid as enrolled inside)", $cid);
            }
        }

        $this->process_excluded();
        $this->process_metas();
        $this->process_courseareas();

        $this->filter_courses_by_time();
        $this->fix_courses_attributes_for_sorting();
        $this->sort_courses();
    }

    protected function filter_courses_by_time() {
        if (array_key_exists('schedule', $this->options)) {

            if ($this->options['schedule'] == 'all') {
                return;
            }

            $now = time();

            foreach (array_keys($this->courses) as $cid) {
                switch ($this->options['schedule']) {
                    case 'passed': {
                        if ($c->enddate > $now) {
                            unset($this->courses[$cid]);
                        }
                        break;
                    }

                    case 'current': {
                        if ($c->enddate < $now && $c->startdate > $now) {
                            unset($this->courses[$cid]);
                        }
                        break;
                    }

                    case 'future': {
                        if ($c->startdate < $now) {
                            unset($this->courses[$cid]);
                        }
                        break;
                    }
                }
            }
        }
    }

    protected function has_content($template) {
        return !empty($this->courses);
    }

    protected function get_course_sort_option_templates() {

        $defaultsortoption = get_config('local_my', 'defaultcoursesortoption');
        $lightfavorites = get_config('local_my', 'lightfavorites');

        if (empty($defaultsortoption)) {
            $defaultsortoption = 'byname';
        }

        $options = [
            'byname',
            'byenddate',
            'bycompletion',
            'bylastaccess',
        ];

        if ($lightfavorites) {
            $options[] = 'byfavorites';
        }

        $opttpls = [];

        foreach ($options as $option) {
            $opttpl = new StdClass;
            $opttpl->value = $option;
            $opttpl->optionlabelstr = get_string($option, 'local_my');
            $opttpl->active = $defaultsortoption == $option; // At the moment, not bound to user preferences. Next step.
            $opttpl->optionarialabelstr = get_string('ariaviewselectoroption', 'local_my', $opttpl->optionlabelstr);
            $opttpls[] = $opttpl;
        }

        return $opttpls;
    }

    protected function get_course_time_option_templates() {

        $defaultsortoption = get_config('local_my', 'defaultcoursesortoption');
        if (empty($defaultsortoption)) {
            $defaultsortoption = 'all';
        }

        $options = [
            'all',
            'passed',
            'current',
            'future',
            'hidden',
        ];

        $opttpls = [];

        foreach ($options as $option) {
            $opttpl = new StdClass;
            $opttpl->value = $option;
            $opttpl->optionlabelstr = get_string($option, 'local_my');
            $opttpl->active = $defaultsortoption == $option; // At the moment, not bound to user preferences. Next step.
            $opttpl->optionarialabelstr = get_string('ariaviewtimeoption', 'local_my', $opttpl->optionlabelstr);
            $opttpls[] = $opttpl;
        }

        return $opttpls;
    }

    protected function get_course_display_option_templates() {

        $defaultdisplayoption = get_config('local_my', 'defaultcoursedisplayoption');
        if (empty($defaultsortoption)) {
            $defaultdisplayoption = 'displaycards';
        }

        $options = [
            'displayauto',
            'displaycards',
            'displaylist',
            'displaysummary',
        ];

        $opttpls = [];

        foreach ($options as $option) {
            $opttpl = new StdClass;
            $opttpl->value = $option;
            $opttpl->optionlabelstr = get_string($option, 'local_my');
            $opttpl->active = $defaultdisplayoption == $option; // At the moment, not bound to user preferences. Next step.
            $opttpl->optionarialabelstr = get_string('to'.$option, 'local_my');
            $opttpls[] = $opttpl;
        }

        return $opttpls;
    }
}