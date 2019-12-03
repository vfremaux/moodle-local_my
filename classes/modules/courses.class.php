<?php

namespace local_my\module;

use Stdclass;
use context_course;

class courses_module extends module {

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
        $template->modulename = $this->modulename;
        $template->buttons = $this->get_buttons();

        if (!$this->has_content($template)) {
            $template->hascourses = false;
            $template->nocourses = $OUTPUT->notification(get_string('nocourses', 'local_my'));
            return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
        }

        $template->hascourses = true;
        $template->required = $required; // prefered view type.

        $this->resolve_viewtype($template);

        if (!empty($template->asflatlist) || !empty($template->asgrid)) {

            if (!empty($template->asflatlist)) {
                $this->options['gaugetype'] = 'sektor';
                $this->options['gaugewidth'] = '20';
                $this->options['gaugeheight'] = '20';
            }

            // Get a simple, one level list.
            foreach ($this->courses as $cid => $c) {
                $coursetpl = $this->export_course_for_template($c);
                $template->courses[] = $coursetpl;
            }
        } else {
            // as list.
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
        $this->exclude_post_display('mycourses');

        $template->debuginfo = self::get_debuginfo();
        return $OUTPUT->render_from_template('local_my/my_courses_module', $template);
    }

    public function get_courses() {
        global $USER, $DB, $CFG;

        // This module do not get its own course list, as it is loaded from outside.
        assert(1);
    }

    public function set_courses($courses) {
        $this->courses = $courses;
    }

    public function set_options($options) {
        $this->options = $options;
    }

    protected function has_content($template) {
        return !empty($this->courses);
    }
}