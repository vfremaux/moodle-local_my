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
use \context_system;

/**
 * This class allows to invoke a block content as a local_my module.
 *
 */
class block_module extends module {

    protected $blockid;

    public function __construct($blockid = 0) {
        $blockid = str_replace('block_', '', $blockid);
        $this->blockid = $blockid;
        $this->area = 'block';
        $this->modulename = get_string('block', 'local_my');
    }

    public function render($required = '') {
        global $DB, $OUTPUT;

        $template = new StdClass;
        if (!$blockrec = $DB->get_record('block_instances', array('id' => $this->blockid))) {
            $template->error = $OUTPUT->notification(get_string('errorbadblock', 'local_my', $this->blockid));
        } else {
            $blockinstance = block_instance($blockrec->blockname, $blockrec);
            $template->title = $blockinstance->get_title();
            $content = $blockinstance->get_content()->text;
            $template->content = $content;
            $template->blockname = $blockrec->blockname;
            $template->blockid = $this->blockid;
        }
        return $OUTPUT->render_from_template('local_my/block_module', $template);
    }

    public function get_courses() {
        /* get courses to diplay */
    }
}