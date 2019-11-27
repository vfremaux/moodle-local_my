<?php

namespace local_my\module;

use \StdClass;
use \Mobile_Detect;
use \moodle_url;

class my_heatmap_module extends module {

    public function __construct() {
        $this->area = 'my_heatmap';
        $this->modulename = get_string('myactivity', 'local_my');
    }

    public function render($required = '') {
        global $CFG, $USER, $OUTPUT;

        if (empty(self::$config->heatmaprange)) {
            self::$config->heatmaprange = 6;
        }

        $localmyheatmaprange = self::$config->heatmaprange;
        $mb = new Mobile_Detect();
        if ($mb->isMobile()) {
            $localheatmaprange = 3;
        }

        $startdate = time() - (DAYSECS * 30 * ($localmyheatmaprange - 1));
        $startmilli = $startdate * 1000;

        $legendformat = new StdClass();
        // Less than {min} {name}    Formatting of the smallest (leftmost) value of the legend.
        $legendformat->lower = get_string('lower', 'local_my');
        // Between {down} and {up} {name}    Formatting of all the value but the first and the last.
        $legendformat->inner = get_string('inner', 'local_my');
        // More than {max} {name}.
        $legendformat->upper = get_string('upper', 'local_my');
        $jsonlegendformat = json_encode($legendformat);

        $subdomainformat = new StdClass();
        $subdomainformat->empty = '{date}';
        $subdomainformat->filled = get_string('filled', 'local_my');
        $jsonsubdomainformat = json_encode($subdomainformat);

        $monthnames = array('january', 'february', 'march', 'april', 'may', 'june', 'july',
                            'august', 'september', 'october', 'november', 'december');
        array_walk($monthnames, [$this, 'i18n_months']);

        $itemname = get_string('frequentationitem', 'local_my');

        $template = new StdClass;
        $template->area = $this->area;
        $template->monthnames = json_encode($monthnames);
        $template->modulename = $this->modulename;
        $template->wwwroot = $CFG->wwwroot;
        $template->startmilli = $startmilli;
        $template->dataurl = new moodle_url('/local/my/heatlogs.php', ['id' => $USER->id]);
        $template->jsonlegendformat = $jsonlegendformat;
        $template->jsonsubdomainformat = $jsonsubdomainformat;
        $template->itemname = $itemname;
        $template->localmyheatmaprange = $localmyheatmaprange;

        return $OUTPUT->render_from_template('local_my/heatmap_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }

    protected function i18n_months(&$a, $key) {
        $a = get_string($a, 'local_my');
    }
}
