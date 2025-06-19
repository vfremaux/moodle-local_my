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
use context_module;
use moodle_url;

class latest_news_module extends module {

    public function __construct() {
        $this->area = 'latest_news';
        $this->modulename = get_string('latestnews', 'local_my');
    }

    public function render($required = 'plain') {
        global $SITE, $CFG, $SESSION, $USER, $OUTPUT;

        if ($SITE->newsitems) {
            // Print forums only when needed.
            require_once($CFG->dirroot.'/mod/forum/lib.php');

            if (! $newsforumrec = forum_get_course_forum($SITE->id, 'news')) {
                print_error('cannotfindorcreateforum', 'forum');
            }

            $vaultfactory = \mod_forum\local\container::get_vault_factory();
            $forumvault = $vaultfactory->get_forum_vault();
            $newsforum = $forumvault->get_from_id($newsforumrec->id);

            // Fetch news forum context for proper filtering to happen.
            // $newsforumcm = get_coursemodule_from_instance('forum', $newsforumrec->id, $SITE->id, false, MUST_EXIST);
            $res = get_course_and_cm_from_instance($newsforumrec->id, 'forum');
            $newsforumcm = $res[1];
            $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

            $template = new StdClass();

            $template->forumname = format_string($newsforum->get_name(), true, array('context' => $newsforumcontext));
            $template->area = $this->area;
            $template->modulename = $this->modulename;

            if (isloggedin()) {
                $SESSION->fromdiscussion = $CFG->wwwroot;
                if (\mod_forum\subscriptions::is_subscribed($USER->id, $newsforumrec)) {
                    if (!\mod_forum\subscriptions::is_forcesubscribed($newsforumrec)) {
                        $template->subscribestr = get_string('unsubscribe', 'forum');
                    }
                } else {
                    $template->subscribestr = get_string('subscribe', 'forum');
                }
                $params = array('id' => $newsforumrec->id, 'sesskey' => sesskey());
                $template->subscribeurl = new moodle_url('/mod/forum/subscribe.php', $params);
                $template->isloggedin = true;
            }

            // Need capture HTML raw output.
            // ob_start();
            // forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, $required, 'p.modified DESC');
            $pageno = optional_param('p', 0, PARAM_INT);
            $pagesize = optional_param('s', 0, PARAM_INT);
            $displaymode = FORUM_MODE_FLATNEWEST;
            $discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();
            $sortorder = $discussionlistvault::SORTORDER_LASTPOST_DESC;
            $groupid = groups_get_activity_group($newsforumcm, true) ?: null;
            $rendererfactory = \mod_forum\local\container::get_renderer_factory();
            $discussionsrenderer = $rendererfactory->get_discussion_list_renderer($newsforum);
            $template->lastdiscussions = $discussionsrenderer->render($USER, $newsforumcm, $groupid, $sortorder, $pageno, $pagesize, $displaymode);

            // $template->lastdiscussions .= ob_get_clean();

            return $OUTPUT->render_from_template('local_my/latest_news_module', $template);
        }

        return '';
    }

    public function get_courses() {
        // no course related.
        assert(1);
    }
}