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

use \StdClass;
use \moodle_url;
use \context_course;

class course_categories_module extends module {

    protected $options;

    protected $categories;

    public function __construct() {
        global $DB;

        parent::__construct();
        $this->area = 'course_categories';
        $this->modulename = get_string('categories', 'local_my');

        $this->options = array();
        $this->options['withicons'] = false;

        // Get categories to display.
        $this->categories = explode(',', self::$config->categoryarea0);
    }

    public function render($required = 'aslist') {
        global $OUTPUT, $DB, $PAGE, $USER;

        $template = new StdClass();

        $template->$required = true;
        $template->area = $this->area;

        $template->debuginfo = self::get_debuginfo();

        if (!empty(self::$config->effect_opacity)) {
            $template->withopacityeffect = 'with-opacity-effect';
        }

        if (!empty(self::$config->effect_halo)) {
            $template->withhaloeffect = 'with-halo-effect';
        }

        $template->totalofcategories = 0;
        if (!empty($this->categories)) {
            foreach ($this->categories as $catid) {
                try {
                    $category = local_get_category($catid);
                    $cattpl = $this->export_course_category_for_template($category, $this->options);
                    $template->categories[] = $cattpl;
                    $template->hascategories = true;
                    $template->totalofcategories++;
                } catch (moodle_exception $ex) {
                    assert(1);
                }
            }
        }

        return $OUTPUT->render_from_template('local_my/course_categories_module', $template);
    }

    public function get_courses() {
        assert(1);
    }
}