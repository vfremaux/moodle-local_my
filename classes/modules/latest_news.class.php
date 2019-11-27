<?php

namespace local_my\module;

use StdClass;
use context_module;

class latest_news_module extends module {

    public function __construct() {
        $this->area = 'latest_news';
        $this->modulename = get_string('latestnews', 'local_my');
    }

    public function render($required = 'plain') {
        global $SITE, $CFG, $SESSION, $USER;

        if ($SITE->newsitems) {
            // Print forums only when needed.
            require_once($CFG->dirroot.'/mod/forum/lib.php');

            if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
                print_error('cannotfindorcreateforum', 'forum');
            }

            // Fetch news forum context for proper filtering to happen.
            $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
            $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

            $template = new StdClass();

            $template->forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
            $template->area = $this->area;
            $template->modulename = $this->modulename;

            if (isloggedin()) {
                $SESSION->fromdiscussion = $CFG->wwwroot;
                if (\mod_forum\subscriptions::is_subscribed($USER->id, $newsforum)) {
                    if (!\mod_forum\subscriptions::is_forcesubscribed($newsforum)) {
                        $template->subscribestr = get_string('unsubscribe', 'forum');
                    }
                } else {
                    $template->subscribestr = get_string('subscribe', 'forum');
                }
                $params = array('id' => $newsforum->id, 'sesskey' => sesskey());
                $template->subscribeurl = new moodle_url('/mod/forum/subscribe.php', $params);
                $template->isloggedin = true;
            }

            // Need capture HTML raw output.
            ob_start();
            forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, $required, 'p.modified DESC');
            $template->lastdiscussions .= ob_get_clean();

            return $OUTPUT->render_from_template('local_my/latest_news_module', $template);
        }

        return '';
    }

    public function get_courses() {
        // no course related.
        assert(1);
    }
}