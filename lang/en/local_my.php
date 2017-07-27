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

$string['my:overridemy'] = 'Can override My routing';
$string['my:ismanager'] = 'Has manager activity in site';
$string['my:isteacher'] = 'Has teacher activity in some courses';
$string['my:isauthor'] = 'Has editing teacher activity in some courses';

$string['adminview'] = '<span class="tinytext">This content is only visible if user profile {$a->field} is "{$a->value}</span>"';
$string['enabledusers'] = 'Active accounts';
$string['suspendedusers'] = 'Suspended accounts';
$string['connectedusers'] = 'Connected (once) accounts';
$string['onlineusers'] = 'Online users (5 min.)';
$string['opencourses'] = 'Oen courses';
$string['futurecourses'] = 'Future courses';
$string['filestorage'] = 'File storage size';
$string['numberoffiles'] = 'Number of files';
$string['notcompiledyet'] = 'Stats were never compiled yet. you can wait the next stats task run, or <a href="{$a}">force compile now (it may take some time)</a>';
$string['task_compile_stats'] = 'Site wide stats';
$string['asadmin'] = 'Administrator';
$string['asstudent'] = 'Student';
$string['asteacher'] = 'Teacher';
$string['available'] = 'Available';
$string['availablecourses'] = 'Available courses for free enrol';
$string['backtohome'] = 'Back to home page';
$string['cachedef_heatmap'] = 'User\'s heatmap data';
$string['categorysettings'] = 'Category Settings';
$string['choosecategory'] = 'Choose a category for creating course';
$string['choosecoursetoenrollin'] = 'Choose a course to enrol in';
$string['contentfor'] = 'Content for people having \'{$a->profile}\' being \'{$a->data}\'';
$string['completion'] = 'Completion: {$a}%';
$string['courseareasettings'] = 'Course Areas Settings';
$string['coursecreation'] = 'Course creation';
$string['coursesearch'] = 'Search courses';
$string['enrollablecourses'] = 'Courses you can self enrol in';
$string['errorbadblock'] = 'Bad block instance (maybe not in context)';
$string['editing'] = 'You can edit this course';
$string['fieldnotfound'] = 'The field {$a} was not found';
$string['frequentationitem'] = "page";
$string['frequentationitemplural'] = "pages";
$string['heatmapsettings'] = 'Heatmap Settings';
$string['localmycoursearea'] = 'Course area ';
$string['localmycourseareas'] = 'Specific course areas';
$string['localmyenable'] = 'Enable customized my';
$string['localmyexcludedcourses'] = 'Course to exclude';
$string['localmyforce'] = 'Force my page to users';
$string['localmyheatmaprange'] = 'HeatMap Range';
$string['localmymaxavailablelistsize'] = 'Max size of available courses';
$string['localmymaxoverviewedlistsize'] = 'Max size of course list with full overviewes';
$string['localmymaxuncategorizedlistsize'] = 'Max size of course list with mention of category';
$string['localmymodules'] = 'Student panel modules ';
$string['localmyteachermodules'] = 'Teacher panel modules ';
$string['localmyadminmodules'] = 'Admin panel modules ';
$string['localmyprintcategories'] = 'Print with categories (1 level)';
$string['localmyuselefteditioncolumn'] = 'Use left editorial column';
$string['localskipmymetas'] = 'Skip my metacourses';
$string['managemycourses'] = 'Manage my courses';
$string['myactivity'] = 'My activity';
$string['myauthoringcourses'] = 'My authoring courses';
$string['mycalendar'] = 'My calendar';
$string['mycategories'] = 'My authoring categories';
$string['mycourses'] = 'My courses';
$string['myteachings'] = 'My teaching';
$string['mynetwork'] = 'My network';
$string['mytemplates'] = 'My templates';
$string['myteachercourses'] = 'My courses as a teacher';
$string['newcourse'] = 'Create new course';
$string['newcoursefromtemplate'] = 'Create course from template';
$string['newtemplate'] = 'New template';
$string['noavailablecourses'] = 'No course in free access.';
$string['nocourseareas'] = 'No course areas';
$string['nocourses'] = 'No courses.';
$string['pluginname'] = 'Enhanced my';
$string['recentcourses'] = 'Most Recent Courses ';
$string['restorecourse'] = 'Restore a course';
$string['rendererimages'] = 'Renderer images';
$string['seealllist'] = '... see more courses.';
$string['seeallnews'] = 'See all news...';
$string['sitestats'] = 'Site stats';
$string['standardcreation'] = 'Create a new empty course';
$string['templateinitialisationadvice'] = 'No templates. Administrator should create first template before all other users can create their own.';
$string['unknownmodule'] = 'Unknown output module {$a}';
$string['staticguitextsnotinstalled'] = 'You need installing the <a href="https://github.com/vfremaux/moodle-local_staticguitexts">local_staticguitexts</a> plugin for this widget being used.';
$string['lower'] = "Less than {min} {name}";
$string['inner'] = "Between {down} and {up}";
$string['upper'] = "More than {max} {name}";
$string['filled'] = '{count} {name} on {date}';

$string['january'] = 'January';
$string['february'] = 'February';
$string['march'] = 'March';
$string['april'] = 'April';
$string['may'] = 'May';
$string['june'] = 'June';
$string['july'] = 'July';
$string['august'] = 'August';
$string['september'] = 'September';
$string['october'] = 'October';
$string['november'] = 'November';
$string['december'] = 'December';

$string['localmymodules_desc'] = '
<br><p>Page modules can be assembled to build the full dashboard view. Mention any module in order
(one per line. Adding an "-L" suffix will force the module in the left column stack
if dual stack is enabled by adding the "left_edition_column" module in the list.</p>
<p>Default modules:</p>
<li>me: short user identity block</li>
<li>my_courses</li>
<li>my_templates</li>
<li>authored_courses</li>
<li>available_courses</li>
<li>course_areas: special dedicated course areas</li>
<p>Other modules can be used:</p>
<li>latestnews_full</li>
<li>latestnews_headers</li>
<li>my_network: user\'s accessible network</li>
<li>fullme: complete identity block</li>
<li>my_calendar</li>
<li>recent_courses</li>
<li>my_templates (needs local plugin course_templates)</li>
<li>static<n></li>
';

$string['localmyteachermodules_desc'] = '
An optional module set that adds separate panel for teachers.
';

$string['localmyadminmodules_desc'] = '
An optional module set that adds separate panel for admins.
';

$string['rendererimages_desc'] = 'All images for renderer. We expect a "coursedefaultimage" image';

$string['localmycourseareas_desc'] = 'Specific course areas are courses lists picked into a specific head category';

$string['localmyenable_desc'] = 'If activated, the customized my replaces standard my page';

$string['localmyforce_desc'] = 'If enabled, users will be forced to My page, unless they have myoverride capability
(this is related to a block navigation hack. See README)';

$string['localmyheatmaprange_desc'] = 'Range (in months) of the heatmap';

$string['localmymaxavailablelistsize_desc'] = 'Sets the max number of available courses that will be printed. Set 0
to full disable list size limit';

$string['localmymaxoverviewedlistsize_desc'] = 'Sets the max number of courses that will be printed with full activity overview.
Set 0 to full disable overviewes';

$string['localmymaxuncategorizedlistsize_desc'] = 'Over this course count, courses will be shown with mention of category';

$string['localmyprintcategories_desc'] = 'Print with categories (1 level) if enabld';

$string['localmyuselefteditioncolumn_desc'] = 'If enabled, prints a left editorial column for admins to put text or institutional
communication inside';

$string['localskipmymetas_desc'] = 'Skip metacourses in my if enabled';

$string['localmyexcludedcourses_desc'] = 'Enter a list of course ids';
