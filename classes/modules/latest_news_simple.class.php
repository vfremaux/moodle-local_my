<?php

namespace local_my\module;

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

            $newsdiscussions = $DB->get_records('forum_discussions', array('forum' => $newsforum->id), 'timemodified DESC');
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