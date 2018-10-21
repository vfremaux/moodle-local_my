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
 * Course and category management interfaces.
 *
 * @package    local_my
 * @copyright  2017 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_my\output;

use \html_writer;
use \coursecat;
use \single_select;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/classes/management_renderer.php');
require_once($CFG->dirroot.'/local/my/lib.php');

class management_renderer extends \core_course_management_renderer implements \renderable, \templatable {

    /**
     * Displays a heading for the management pages.
     *
     * @param string $heading The heading to display
     * @param string|null $viewmode The current view mode if there are options.
     * @param int|null $categoryid The currently selected category if there is one.
     * @return string
     */
    public function management_heading($heading, $viewmode = null, $categoryid = null) {
        $html = html_writer::start_div('coursecat-management-header clearfix');
        if (!empty($heading)) {
            $html .= $this->heading($heading);
        }
        if ($viewmode !== null) {
            $html .= html_writer::start_div();
            // $html .= $this->view_mode_selector(\core_course\management\helper::get_management_viewmodes(), $viewmode);
            if ($viewmode === 'courses') {
                // CHANGE+ : Get either case.
                $managecategories = coursecat::make_categories_list(array('moodle/category:manage'));
                $coursecreatecategories = coursecat::make_categories_list(array('moodle/course:create'));
                $categories = $managecategories + $coursecreatecategories;

                $authorcourses = local_get_my_authoring_courses('id,fullname,shortname,category',
                                                                'local/my:isteacher', array_keys($categories));
                // Foreach unchecked authored course, add category and all parents in catlist.
                if ($authorcourses) {
                    foreach ($authorcourses as $cid => $course) {
                        $catobj = coursecat::get($course->category);
                        $authorcategories[$course->category]['name'] = $catobj->name;
                        $authorcategories[$course->category]['path'] = $catobj->path;
                        /*
                        $parents = $catobj->get_parents();
                        if ($parents) {
                            foreach ($parents as $pcatid) {
                                if (!array_key_exists($pcatid, $authorcategories)) {
                                    $pcatobj = coursecat::get($pcatid);
                                    $authorcategories[$pcatid]['name'] = $pcatobj->name;
                                    $authorcategories[$pcatid]['path'] = $pcatobj->path;
                                }
                            }
                        }
                        */
                    }

                    // Now build the array of strings to return, mind $separator and $excludeid.
                    $separator = ' / ';
                    foreach (array_keys($authorcategories) as $id) {
                        if (!array_key_exists($id, $categories)) {
                            $path = preg_split('|/|', $authorcategories[$id]['path'], -1, PREG_SPLIT_NO_EMPTY);
                            $namechunks = array();
                            foreach ($path as $parentid) {
                                $namechunks[] = $authorcategories[$parentid]['name'];
                            }
                            $categories[$id] = implode($separator, $namechunks);
                        }
                    }
                }

                // Finalize sorting.
                // TODO This breaks the sortorder order.
                asort($categories);

                // CHANGE.
                $nothing = false;
                if ($categoryid === null) {
                    $nothing = array('' => get_string('selectacategory'));
                    $categoryid = '';
                }
                $select = new single_select($this->page->url, 'categoryid', $categories, $categoryid, $nothing);
                $html .= $this->render($select);
            }
            $html .= html_writer::end_div();
        }
        $html .= html_writer::end_div();
        return $html;
    }

}