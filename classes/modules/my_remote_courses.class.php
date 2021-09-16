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
require_once($CFG->dirroot.'/local/my/classes/modules/my_remotes.trait.php');

use StdClass;
use context_course;
use html_writer;
use moodle_url;

class my_remote_courses_module extends module {
    use my_remotes;

    public function __construct() {
        parent::__construct();
        $this->area = 'my_remote_courses';
        $this->modulename = get_string('myremotecourses', 'local_my');

        // Default gauge settings.
        $this->options = [
            'gaugetype' => 'noprogress',
            'gaugewidth' => 0,
            'gaugeheight' => 0
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

        if (!$this->has_content($template)) {
            // Do NOT bother users with empty remote course set.
            return '';
            /*
            $template->hascourses = false;
            $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
            return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
            */
        }

        $template->hassortorfilter = false;
        /*
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
        */

        $template->hascourses = true;
        $template->required = 'aslist'; // prefered view type.
        $template->aslist = true;

        // View type necessarily as list, with cats giving the remote host identity.

        $this->options['withcats'] = true;
        $this->options['noprogress'] = true;

        // Get a simple, one level list.
        foreach ($this->courses as $hostid => $hostcourses) {
            $cattpl = new StdClass;
            $cattpl->catname = $DB->get_field('mnet_host', 'name', ['id' => $hostid]);
            $cattpl->nolink = true;
            $cattpl->courses = [];
            foreach ($hostcourses as $c) {
                $coursetpl = new StdClass;
                $coursetpl->shortname = $c->shortname;
                $coursetpl->fullname = format_string($c->fullname);
                $coursetpl->summary = format_string($c->summary);
                // We need renegociate the course url to reach the remote course.
                $wantsurl = '/course/view.php?id='.$c->remoteid;
                $coursetpl->courseurl = new moodle_url('/auth/mnet/jump.php', ['hostid' => $hostid, 'wantsurl' => $wantsurl]);

                $cattpl->courses[] = $coursetpl;
            }
            $template->categories[] = $cattpl;
        }
        $template->totalofcourses = count($template->courses);

        // Process exclusion of what has been displayed.
        // Not consistant. courses are remote courses and have NOT local reference.
        /*
        if (empty($this->options['noexcludefromstream'])) {
            $this->exclude_post_display($this->area);
        }
        */

        $template->debuginfo = self::get_debuginfo();

        if (defined('AJAX_SCRIPT') && AJAX_SCRIPT) {
            // Real original module when reloading.
            return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
        } else {
            return $OUTPUT->render_from_template('local_my/my_courses_module-loading_placeholder', $template);
        }
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
//          'hidden',
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