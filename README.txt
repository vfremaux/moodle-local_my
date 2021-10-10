My local component allow to take custom control upon the my page "layout" and structrure in a 
more moodle compliant way.

- Reorganize My layout
- Allow switching back to standard my
- Allow access override for power users against role assigned anywhere in Moodle
- Allow forcing some users in My Page only

It will need several patchs into main index.php and bloc navigation to work.

Recommended patchs :
#######################

Patch in index.php are usefull to allow some people overriding the My Page redirect.



Possible patchs :
#######################

In block/navigation/block_navigation.php, in get content :

        // Get the navigation object or don't display the block if none provided.
        if (!$navigation = $this->get_navigation()) {
            return null;
        }

        // PATCH : Removes link to home when forced my page
        // removes access to home for forced users
        $myoverride = false;

        if (has_capability('local/my:overridemy', context_system::instance()) && local_has_myoverride_somewhere()){
            $myoverride = true;
        }

        if ($CFG->localmyforce && !$myoverride) {
            $navigation->children->remove('home', 70);
        }
        // /PATCH

this requires local/my/lib.php being loaded earlier (f.e. in config.php)

2017072100
=========================================

Add capability local/my:ismanager to allow non siteadmins to have admin tab.

2017082200
=========================================

Add manager as being an author when assigned in some context

2019050300 - XXXX.0006
=========================================

Add more settings to control some elements visibility on widgets. Add
capability to hide/show course attributes on grid/boxed views.

XX.0011
=========================================

Privatize some widgets and the detailed pedagogic output.

2021031000 - XX.0013
=========================================

Add LTC override to course progression indicators, if exists in course.
