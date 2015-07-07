<?php

require_once($CFG->dirroot.'/local/my/lib.php');
/** 
 * thi hook redraws the my routing policy using local/my:oveeridemy switch and my force setting.
 */
if (get_home_page() != HOMEPAGE_SITE) {
    // Redirect logged-in users to My Moodle overview if required
    if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
    } elseif (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_MY)) {
        if ($CFG->localmyforce && !local_has_myoverride_somewhere()) {
            redirect($CFG->wwwroot .'/my/');
        }
        if (optional_param('redirect', 1, PARAM_BOOL) === 1) {
            redirect($CFG->wwwroot .'/my/');
        }
    } else if (!empty($CFG->defaulthomepage) && ($CFG->defaulthomepage == HOMEPAGE_USER)) {
        $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'), new moodle_url('/', array('setdefaulthome'=>true)), navigation_node::TYPE_SETTING);
    }
}
