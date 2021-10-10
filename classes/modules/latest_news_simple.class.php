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

use StdClass;
use moodle_url;

class latest_news_simple_module extends module {

    public function __construct() {
        $this->area = 'latest_news';
        $this->modulename = get_string('latestnews', 'local_my');
    }

    public function render($required = 'plain') {
        global $PAGE, $SITE, $CFG, $OUTPUT, $DB, $SESSION;

        if ($SITE->newsitems) {
            // Print forums only when needed.
            require_once($CFG->dirroot .'/mod/forum/lib.php');

            if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
                print_error('cannotfindorcreateforum', 'forum');
            }

            $template = new StdClass();
            $template->forumname = format_string($newsforum->name);
            $template->simple = true;

            $newsdiscussions = $DB->get_records('forum_discussions', array('forum' => $newsforum->id), 'timemodified DESC', '*', 0, $SITE->newsitems);
            foreach ($newsdiscussions as $news) {
                $discussiontpl = new StdClass();
                $discussiontpl->discussionurl = new moodle_url('/mod/forum/discuss.php', array('d' => $news->id));
                $discussiontpl->newstitle = format_string($news->name);
                $discussiontpl->timemodified = userdate($news->timemodified);
                $template->discussions[] = $discussiontpl;
            }
            $template->forumlink = self::$renderer->print_forum_link($newsforum, $newsforum->name);

            return $OUTPUT->render_from_template('local_my/latest_news_module', $template);
        }

        return '';
    }

    public function get_courses() {
        // no course related.
        assert(1);
    }
}