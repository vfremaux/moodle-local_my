<?php

/**
 * This file contains content output modules for the my page. 
 *
 *
 *
 */
define('MAX_COURSE_OVERVIEWED_LIST', 20);

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_my_courses(&$excludedcourses) {
    global $OUTPUT, $DB, $CFG;

    $mycourses = enrol_get_my_courses('id,shortname,fullname');

    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            unset($mycourses[$id]);
        }
    }

    foreach ($mycourses as $id => $c) {
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    $str = '';

    $str .= '<div class="block block_my_courses">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('mycourses', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';
    $str .= '<table id="mycourselist" width="100%" class="courselist">';

    if (empty($mycourses)) {
        $str .= '<tr valign="top">';
        $str .= '<td>';
        $str .= get_string('nocourses', 'local_my');
        $str .= '</td>';
        $str .= '</tr>';
    } else {
        $str .= '<tr valign="top"><td>';
        if (count($mycourses) < $CFG->localmymaxoverviewedlistsize) {
            $str .= local_print_course_overview($mycourses, true);
        } else {
            // Solve a performance issue for people having wide access to courses.
            $str .= local_my_print_courses('mycourses', $mycourses, array());
        }
        $str .= '</td></tr>';

        $excludedcourses = $excludedcourses + $mycourses;
    }

    $str .= '</table>';
    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_authored_courses(&$excludedcourses) {
    global $OUTPUT, $CFG, $DB;

    $myauthcourses = local_get_my_authoring_courses();

    // Pre version 2.5 specific
    // $parents = null;
    // make_categories_list($mycatlist, $parents, 'moodle/course:create');

    // Post 2.5.
    include_once($CFG->dirroot.'/lib/coursecatlib.php');
    $mycatlist = coursecat::make_categories_list('moodle/course:create');

    if (!empty($excludedcourses)) {
        foreach (array_keys($excludedcourses) as $cid) {
            unset($myauthcourses[$cid]);
        }
    }

    $str = '';

    $hascontent = false;
    if (!empty($mycatlist) || !empty($myauthcourses)) {
        $str .= '<div class="block block_my_authored_courses">';
        $str .= '<div class="header">';
        $str .= '<div class="title">';
        $str .= '<h2>'.get_string('myauthoringcourses', 'local_my').'</h2>';
        $str .= '</div>';
        $str .= '</div>';
        $str .= '<div class="content">';
        $hascontent = true;
    }

    if (!empty($mycatlist)) {
        $button1 = $OUTPUT->single_button(new moodle_url('/local/my/create_course.php'), get_string('newcourse', 'local_my'));
        $button2 = '';
        if (is_dir($CFG->dirroot.'/local/coursetemplates')) {
            $config = get_config('local_coursetemplates');
            if ($config->enabled && $config->templatecategory) {
                if ($DB->count_records('course', array('category' => $config->templatecategory, 'visible' => 1))) {
                    $button2 = $OUTPUT->single_button(new moodle_url('/local/coursetemplates/index.php'), get_string('newcoursefromtemplate', 'local_my'));
                }
            }
        }
        $str .= '<div class="right-button">'.$button1.' '.$button2.'</div>';
    }

    if (!empty($myauthcourses)) {
        $str .= '<table id="myauthoredcourselist" width="100%" class="generaltable courselist">';
        $str .= '<tr valign="top"><td>';
        if (count($myauthcourses) < $CFG->localmymaxoverviewedlistsize) { 
            $str .= local_print_course_overview($myauthcourses, true);
        } else {
            // Solve a performance issue for people having wide access to courses.
            $str .= local_my_print_courses('myauthcourses', $myauthcourses, array('noheading' => 1), true);
        }
        $str .= '</td></tr>';
        $str .= '</table>';

        $excludedcourses = $excludedcourses + $myauthcourses;
    }

    if ($hascontent) {
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}

/**
 * Prints the "classical" "My Courses" area
 */
function local_my_print_my_templates(&$excludedcourses) {
    global $OUTPUT, $CFG, $DB;

    $config = get_config('local_coursetemplates');

    if (!$config->templatecategory) {
        return '';
    }

    $mytemplates = local_get_my_templates();

    // Pre version 2.5 specific
    // $parents = null;
    // make_categories_list($mycatlist, $parents, 'moodle/course:create');
    $templatecatcontext = context_coursecat::instance($config->templatecategory);
    if (!has_capability('moodle/course:create', $templatecatcontext)) {
        return '';
    }

    // post 2.5

    if (!empty($excludedcourses)) {
        foreach (array_keys($excludedcourses) as $cid) {
            unset($mytemplates[$cid]);
        }
    }

    $str = '';

    $str .= '<div class="block block_my_templates">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2>'.get_string('mytemplates', 'local_my').'</h2>';
    $str .= '</div>';
    $str .= '</div>';
    $str .= '<div class="content">';

    $button1 = '';
    if (is_dir($CFG->dirroot.'/local/coursetemplates')) {
        $config = get_config('local_coursetemplates');
        if ($config->enabled && $config->templatecategory) {
            if ($DB->count_records('course', array('category' => $config->templatecategory, 'visible' => 1))) {
                $button1 = $OUTPUT->single_button(new moodle_url('/local/coursetemplates/index.php', array('category' => $config->templatecategory, 'forceediting' => true)), get_string('newtemplate', 'local_my'));
            }
        }
    }
    $str .= '<div class="right-button">'.$button1.'</div>';

    if (!empty($mytemplates)) {
        // $str .= '<table id="mytemplatelist" width="100%" class="generaltable courselist">';
        // $str .= '<tr valign="top"><td>';
        $str .= local_my_print_courses('mytemplates', $mytemplates, array('noheading' => 1), true);
        // $str .= '</td></tr>';
        // $str .= '</table>';

        $excludedcourses = $excludedcourses + $mytemplates;
    }

    $str .= '</div>';
    $str .= '</div>';

    return $str;
}

/**
 * Prints the specific courses area as a 3 column link list
 */
function local_my_print_course_areas(&$excludedcourses) {
    global $USER, $CFG, $OUTPUT, $DB;

    $allcourses = enrol_get_my_courses('id,shortname,fullname');

    /*
    if (!empty($excludedcourses)){
        foreach($excludedcourses as $id => $c){
            unset($allcourses[$id]);
        }
    }
    */

    foreach($allcourses as $id => $c) {
        $allcourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    if (empty($CFG->localmycourseareas)) return; // performance quick trap

    $str = '';

    $str .= '<table id="mycourseareas" width="100%">';
    $str .= '<tr valign="top">';

    $reali = 1;
    for ($i = 0; $i < $CFG->localmycourseareas ; $i++) {

        $coursearea = 'localmycoursearea'.$i;
        $mastercategory = $DB->get_record('course_categories', array('id' => $CFG->$coursearea));

        $key = 'localmycoursearea'.$i;
        $categoryid = $CFG->$key;

        // filter courses of this area
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        $areacourses = array();
        foreach($allcourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $areacourses[$c->id] = $c;
                $excludecourses[$c->id] = 1;
            }
        }

        switch (count($areacourses)) {
            case 1 : $colwidth = 100 ; break;
            case 2 : $colwidth = 50 ; break;
            default: $colwidth = 33 ;
        }

        if (!empty($areacourses)) {
            if ($reali % 3 == 0) {
                $str .= '</tr></tr valign="top">';
            }
            $str .= '<td width="'.$colwidth.'%">';

            $str .= $OUTPUT->heading(format_string($mastercategory->name), 2, 'headingblock header');
            $str .= '<div class="block">';
            $str .= '<table id="courselistarea'.$reali.'" width="100%" class="courselist generaltable">';
            $str .= '<tr valign="top"><td>';
            if (count($areacourses) < $CFG->localmymaxoverviewedlistsize) { 
                $str .= local_print_course_overview($areacourses, true);
            } else {
                // Solve a performance issue for people having wide access to courses.
                $str .= local_print_simple_course_view($areacourses, true);
            }
            $str .= '</td></tr>';
            $str .= '</table>';
            $str .= '</div>';

            $str .= '</td>';

            $reali++;
        }
    }

    $str .= '</tr>';
    $str .= '</table>';

    return $str;
}

/**
 * Prints the specific courses area as a 3 column link list
 */
function local_my_print_course_areas_and_availables(&$excludedcourses) {
    global $USER, $CFG, $OUTPUT, $DB;
    
    $mycourses = enrol_get_my_courses('id,shortname,fullname');
    $availablecourses = local_get_enrollable_courses();

    if (empty($mycourses)) $mycourses = array(); // be sure of that !
    if (empty($availablecourses)) $availablecourses = array(); // be sure of that !
    
    if (!empty($excludedcourses)) {
        foreach ($excludedcourses as $id => $c) {
            if (array_key_exists($id, $mycourses)) {
                unset($mycourses[$id]);
            }
            if (array_key_exists($id, $availablecourses)) {
                unset($availablecourses[$id]);
            }
        }
    }

    foreach ($mycourses as $id => $c) {
        $mycourses[$id]->lastaccess = $DB->get_field('log', 'max(time)', array('course' => $id));
    }

    if (!$CFG->localmycourseareas) return; // performance quick trap

    $str = '';

    $str .= '<table id="mycourseareas" width="100%">';
    $str .= '<tr valign="top">';

    $options['noheading'] = 1;
    $options['nooverview'] = 1;
    $options['withdescription'] = 1;

    $reali = 1;
    for ($i = 0; $i < $CFG->localmycourseareas ; $i++) {

        $coursearea = 'localmycoursearea'.$i;
        $mastercategory = $DB->get_record('course_categories', array('id' => $CFG->$coursearea));

        $key = 'localmycoursearea'.$i;
        $categoryid = $CFG->$key;

        // filter courses of this area
        $retainedcategories = local_get_cat_branch_ids_rec($categoryid);
        $myareacourses = array();
        foreach ($mycourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $myareacourses[$c->id] = $c;
                $excludecourses[$c->id] = 1;
            }
        }

        $availableareacourses = array();
        foreach ($availablecourses as $c) {
            if (in_array($c->category, $retainedcategories)) {
                $availableareacourses[$c->id] = $c;
                $excludecourses[$c->id] = 1;
            }
        }
    
        if (!empty($myareacourses) || !empty($availableareacourses)) {
            if ($reali % 4 == 0) {
                $str .= '</tr><tr valign="top">';
            }
            $str .= '<td width="33%">';

            $str .= $OUTPUT->heading(format_string($mastercategory->name), 2, 'headingblock header');
            $str .= '<div class="block">';
            $str .= '<table id="courselistarea'.$reali.'" width="100%" class="courselist">';
            $str .= '<tr valign="top">';
            $str .= '<td>';
            if (empty($options['nooverview'])) {
                if (count($myareacourses) < $CFG->localmymaxoverviewedlistsize) {
                    $str .= local_print_course_overview($myareacourses, true);
                } else {
                    // Solve a performance issue for people having wide access to courses.
                    $str .= local_print_simple_course_view($myareacourses, true);
                }
            } else {
                // aggregate my courses with the available and print in one unique list
                $availableareacourses = $myareacourses + $availableareacourses;
            }
            if (!empty($availableareacourses)) {
                // $str .= $OUTPUT->heading(get_string('available', 'local_my'), 3, 'local-my-area-availables');
                $str .= local_my_print_courses('available', $availableareacourses, $options, true);
            }
            $str .= '</td>';
            $str .= '</tr>';
            $str .= '</table>';
            $str .= '</div>';

            $str .= '</td>';

            $reali++;
        }
    }

    $str .= '</tr>';
    $str .= '</table>';

    return $str;
}

/**
 * Prints the available (enrollable) courses as simple link entries
 */
function local_my_print_available_courses(&$excludedcourses) {

    $str = '';

    $courses = local_get_enrollable_courses();
    if (empty($courses)) {
        return;
    }

    $overcount = (count($courses) > 12);
    if ($overcount) {
        $courses = array_slice($courses, 0, 11);
    }

    if (!empty($excludedcourses)) {
        $excludedids = array_keys($excludedcourses);
    } else {
        $excludedids = array();
    }
    foreach ($courses as $cid => $foo) {
        if (in_array($cid, $excludedids)) {
            unset($courses[$cid]);
        }
    }

    $options['printifempty'] = 0;
    $options['withcats'] = 2;

    $str .= '<div class="block block_my_available_courses">';
    $str .= local_my_print_courses('availablecourses', $courses, $options, true);
    if ($overcount) {
        $allcoursesurl = new moodle_url('/local/my/enrollable_courses.php');
        $str .= '<div class="local-my-overcount"><a href="'.$allcoursesurl.'">'.get_string('seealllist', 'local_my').'</a></div>';
    }
    $str .= '</div>';

    return $str;
}

/**
 * Prints the news forum as a list of full deployed discussions.
 */
function local_my_print_latestnews_full() {
    global $SITE, $CFG, $OUTPUT;

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot .'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // fetch news forum context for proper filtering to happen
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
        echo html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(strip_tags($forumname))), array('href'=>'#skipsitenews', 'class'=>'skip-block'));

        if (isloggedin()) {
            $SESSION->fromdiscussion = $CFG->wwwroot;
            $subtext = '';
            if (forum_is_subscribed($USER->id, $newsforum)) {
                if (!forum_is_forcesubscribed($newsforum)) {
                    $subtext = get_string('unsubscribe', 'forum');
                }
            } else {
                $subtext = get_string('subscribe', 'forum');
            }
            echo '<div class="block block_my_news">';
            echo '<div class="header">';
            echo '<div class="title">';
            echo '<h2>'.$forumname.'</h2>';
            echo '</div>';
            echo '</div>';
            echo '<div class="content">';
            $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
            echo html_writer::tag('div', html_writer::link($suburl, $subtext), array('class' => 'subscribelink'));
            echo '</div>';
        } else {
            echo '<div class="block block_my_news">';
            echo '<div class="header">';
            echo '<div class="title">';
            echo '<h2>'.$forumname.'</h2>';
            echo '</div>';
            echo '</div>';
            echo '<div class="content">';
            echo '</div>';
        }

        forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'plain', 'p.modified DESC');
        echo '</div>';
        echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
    }
}

/**
 * Prints the news forum as simple compact list of discussion headers.
 */
function local_my_print_latestnews_headers() {
    global $SITE, $CFG, $OUTPUT, $USER;
    
    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // Fetch news forum context for proper filtering to happen.
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
        echo html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(strip_tags($forumname))), array('href'=>'#skipsitenews', 'class'=>'skip-block'));

        if (isloggedin()) {
            if (!isset($SESSION)) $SESSION = new StdClass();
            $SESSION->fromdiscussion = $CFG->wwwroot;
            $subtext = '';
            if (forum_is_subscribed($USER->id, $newsforum)) {
                if (!forum_is_forcesubscribed($newsforum)) {
                    $subtext = get_string('unsubscribe', 'forum');
                }
            } else {
                $subtext = get_string('subscribe', 'forum');
            }
            echo '<div class="block block_my_newsheads">';
            echo '<div class="header">';
            echo '<div class="title">';
            echo '<h2>'.$forumname.'</h2>';
            echo '</div>';
            echo '</div>';
            echo '<div class="content">';
            $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
            echo html_writer::tag('div', html_writer::link($suburl, $subtext), array('class' => 'subscribelink'));
            echo '</div>';
        } else {
            echo '<div class="block block_my_newsheads">';
            echo '<div class="header">';
            echo '<div class="title">';
            echo $OUTPUT->heading($forumname, 2, 'headingblock header');
            echo '</div>';
            echo '</div>';
        }

        forum_print_latest_discussions($SITE, $newsforum, $SITE->newsitems, 'header', 'p.modified DESC');
        echo '</div>';
        echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
    }
}

/**
 * Same as "full", but removes all subscription or any discussion commandes.
 */
function local_my_print_latestnews_simple() {
    global $SITE, $CFG, $OUTPUT, $USER, $DB, $SESSION;

    if ($SITE->newsitems) {
        // Print forums only when needed.
        require_once($CFG->dirroot .'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($SITE->id, 'news')) {
            print_error('cannotfindorcreateforum', 'forum');
        }

        // Fetch news forum context for proper filtering to happen.
        $newsforumcm = get_coursemodule_from_instance('forum', $newsforum->id, $SITE->id, false, MUST_EXIST);
        $newsforumcontext = context_module::instance($newsforumcm->id, MUST_EXIST);

        $forumname = format_string($newsforum->name, true, array('context' => $newsforumcontext));
        echo html_writer::tag('a', get_string('skipa', 'access', textlib::strtolower(strip_tags($forumname))), array('href'=>'#skipsitenews', 'class'=>'skip-block'));

        if (isloggedin()) {
            $SESSION->fromdiscussion = $CFG->wwwroot;
            $subtext = '';
            /*
            if (forum_is_subscribed($USER->id, $newsforum)) {
                if (!forum_is_forcesubscribed($newsforum)) {
                    $subtext = get_string('unsubscribe', 'forum');
                }
            } else {
                $subtext = get_string('subscribe', 'forum');
            }*/
            echo '<div class="block">';
            echo '<div class="header">';
            echo '<div class="title">';
            echo '<h2>'.$forumname.'</h2>';
            // $suburl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id, 'sesskey' => sesskey()));
            // echo html_writer::tag('div', html_writer::link($suburl, $subtext), array('class' => 'subscribelink'));
            echo '</div></div>';
        } else {
            echo '<div class="block">';
            echo '<div class="header">';
            echo '<div class="title">';
            echo '<h2>'.$forumname.'</h2>';
            echo '</div></div>';
        }

        echo '<div class="content">';
        echo '<table width="100%" class="newstable">';
        $newsdiscussions = $DB->get_records('forum_discussions', array('forum' => $newsforum->id), 'timemodified DESC');
        foreach($newsdiscussions as $news){
            echo '<tr valign="top"><td width="80%"><a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$news->id.'">'.$news->name.'</a></td><td align="right" width="20%">('.userdate($news->timemodified).')</tr>';
        }
        echo '</table>';
        echo '</div>';
        echo '</div>';

        echo html_writer::tag('span', '', array('class' => 'skip-block-to', 'id' => 'skipsitenews'));
    }
}

/**
 * Prints a static div with content stored into central configuration.
 */
function local_my_print_static($index) {
    global $OUTPUT, $USER, $CFG;

    include_once($CFG->dirroot.'/local/staticguitexts/lib.php');
    echo '<div id="custommystaticarea'.$index.'">';
    local_print_static_text('custommystaticarea'.$index, $CFG->wwwroot.'/my/index.php');
    echo '</div>';
}

/** 
 * Prints a widget with information about me.
 */
function local_my_print_me() {
    global $OUTPUT, $USER, $CFG;

    $context = context_system::instance();

    $identityfields = array_flip(explode(',', $CFG->showuseridentity));

    if (has_capability('moodle/user:viewhiddendetails', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    echo '<div class="userprofilebox clearfix">';
    echo '<div class="profilepicture" style="float:left;margin-right:20px">';
    echo $OUTPUT->user_picture($USER, array('size' => 50));
    echo '</div>';
    echo '<div class="username">';
    echo $OUTPUT->heading(fullname($USER));

    echo '</div>';
    echo '</div>';
}

/**
 * Prints a widget with more information about me.
 */ 
function local_my_print_fullme() {
    global $OUTPUT, $USER, $CFG;

    $context = context_system::instance();

    $identityfields = array_flip(explode(',', $CFG->showuseridentity));

    if (has_capability('moodle/user:viewhiddendetails', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }

    echo '<div class="userprofilebox clearfix">';
    echo '<div class="profilepicture" style="float:left;margin-right:20px">';
    echo $OUTPUT->user_picture($USER, array('size' => 50));
    echo '</div>';
    echo '<div class="username">';
    echo $OUTPUT->heading(fullname($USER));

    echo '<table id="my-me" class="list" width="70%">';

    local_my_print_row(get_string("username").":", "$USER->username");

    if (!isset($hiddenfields['firstaccess'])) {
        if ($USER->firstaccess) {
            $datestring = userdate($USER->firstaccess)."&nbsp; (".format_time(time() - $USER->firstaccess).")";
        } else {
            $datestring = get_string("never");
        }
        local_my_print_row(get_string("firstaccess").":", $datestring);
    }
    if (!isset($hiddenfields['lastaccess'])) {
        if ($USER->lastaccess) {
            $datestring = userdate($USER->lastaccess)."&nbsp; (".format_time(time() - $USER->lastaccess).")";
        } else {
            $datestring = get_string("never");
        }
        local_my_print_row(get_string("lastaccess").":", $datestring);
    }

    if (isset($identityfields['institution']) && $USER->institution) {
        local_my_print_row(get_string("institution").":", "$USER->institution");
    }

    if (isset($identityfields['department']) && $USER->department) {
        local_my_print_row(get_string("department").":", "$USER->department");
    }

    if (isset($identityfields['country']) && !isset($hiddenfields['country']) && $USER->country) {
        local_my_print_row(get_string('country') . ':', get_string($USER->country, 'countries'));
    }

    if (isset($identityfields['city']) && !isset($hiddenfields['city']) && $USER->city) {
        local_my_print_row(get_string('city') . ':', $USER->city);
    }

    if (isset($identityfields['idnumber']) && $USER->idnumber) {
        local_my_print_row(get_string("idnumber").":", "$USER->idnumber");
    }

    echo '</table>';
    echo '</div>';
    echo '</div>';
}

/**
 * A utility function
 */
function local_my_print_row($left, $right) {
    echo "\n<tr valign=\"top\"><th class=\"my-label c0\" width=\"40%\">$left</th><td class=\"info c1\" width=\"60%\">$right</td></tr>\n";
}

/**
 * Prints a github like heat activity map on passed six months
 * @param int $userid the concerned userid
 */
function local_my_print_my_heatmap($userid = 0) {
    global $CFG, $USER;

    if (!$userid) {
        $userid = $USER->id;
    }

    if (empty($CFG->localmyheatmaprange)) {
        $CFG->localmyheatmaprange = 6;
    }

    $startdate = time() - (DAYSECS * 30 * ($CFG->localmyheatmaprange - 1));
    $startmilli = $startdate * 1000;

    $legendformat = new StdClass();
    $legendformat->lower = get_string('lower', 'local_my');  // less than {min} {name}    Formatting of the smallest (leftmost) value of the legend
    $legendformat->inner = get_string('inner', 'local_my');  // between {down} and {up} {name}    Formatting of all the value but the first and the last
    $legendformat->upper = get_string('upper', 'local_my');  // more than {max} {name}
    $jsonlegendformat = json_encode($legendformat);

    $subdomainformat = new StdClass();
    $subdomainformat->empty = '{date}';  // less than {min} {name}    Formatting of the smallest (leftmost) value of the legend
    $subdomainformat->filled = get_string('filled', 'local_my');  // between {down} and {up} {name}    Formatting of all the value but the first and the last
    $jsonsubdomainformat = json_encode($subdomainformat);

    function i18n_months(&$a, $key) {
        $a = get_string($a, 'local_my');
    }

    $monthnames = array('january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december');
    array_walk($monthnames, 'i18n_months');

    $itemname = get_string('frequentationitem', 'local_my');

    $str = '';
    $str .= '<div class="my-modules heatmap">';
    $str .= '<div class="block block_my_heatmap">';
    $str .= '<div class="header">';
    $str .= '<div class="title">';
    $str .= '<h2 class="headingblock header">'.get_string('myactivity', 'local_my').'</h2>';
    $str .= '</div></div>';
    $str .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/local/my/js/d3.v3.min.js"></script>';
    $str .= '<link rel="stylesheet" href="'.$CFG->wwwroot.'/local/my/js/heatmap/cal-heatmap.css" />';
    $str .= '<script type="text/javascript" src="'.$CFG->wwwroot.'/local/my/js/heatmap/cal-heatmap.min.js"></script>';

    // Little trick to get margin top effective against js changes.
    $str .= '<div id="cal-heatmap" style=";margin-top:10px;"></div>';

    $str .= '<script type="text/javascript">
        var monthnames = '.json_encode($monthnames).';
        var cal = new CalHeatMap();
        var startdate = new Date('.$startmilli.');
        cal.init({
            domain:"month", 
            subdomain:"day", 
            start:startdate, 
            data:"'.$CFG->wwwroot.'/local/my/heatlogs.php?id='.$USER->id.'",
            legendTitleFormat:'.$jsonlegendformat.', 
            subDomainTitleFormat:'.$jsonsubdomainformat.', 
            itemName:"'.$itemname.'",
            subDomainDateFormat: 
            function(date) {
                return date.toLocaleDateString();
            },
            domainLabelFormat: function(date) {
                return monthnames[date.getMonth()];
            },
            range:'.$CFG->localmyheatmaprange.'

        });
    </script>';
    $str .= '</div>';
    $str .= '</div>';

    echo $str;
}

/**
 * Prints a module that is the content of the user_mnet_hosts block.
 */
function local_my_print_my_network() {

    $blockinstance = block_instance('user_mnet_hosts');
    $content = $blockinstance->get_content();

    $str = '';

    if (!empty($content->items) || !empty($content->footer)) {

        $str .= '<div class="my-modules network">';
        $str .= '<div class="box block">';
        $str .= '<div class="header">';
        $str .= '<div class="title">';
        $str .= '<h2 class="headingblock">'.get_string('mynetwork', 'local_my').'</h2>';
        $str .= '</div>';
        $str .= '</div>';
        $str .= '<div class="content">';
        if (!empty($content->items)) {
            $str .= '<table width="100%">';
            foreach($content->items as $item) {
                $icon = array_shift($content->icons);
                $str .= '<tr><td>'.$icon.'</td><td>'.$item.'</td></tr>';
            }
            $str .= '</table>';
        }
        if (!empty($content->footer)) {
            $str .= '<p>'.$content->footer.'</p>';
        }
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}

/**
 * prints a module that is the content of the calendar block
 */
function local_my_print_my_calendar() {
    global $PAGE;

    $blockinstance = block_instance('calendar_month');
    $blockinstance->page = $PAGE;
    $content = $blockinstance->get_content();

    if (!empty($content->text) || !empty($content->footer)) {
        $str = '';

        $str .= '<div class="my-modules calendar">';
        $str .= '<div class="box block">';
        $str .= '<h2 class="headingblock header">'.get_string('mycalendar', 'local_my').'</h2>';
        $str .= '<div class="content">';
        if (!empty($content->text)) {
            $str .= $content->text;
        }
        if (!empty($content->footer)) {
            $str .= '<p>'.$content->footer.'</p>';
        }
        $str .= '</div>';
        $str .= '</div>';
    }

    return $str;
}
