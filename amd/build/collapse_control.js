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
 * Javascript controller for controlling the sections.
 *
 * @module     block_multicourse_navigation/collapse_control
 * @package    block_multicourse_navigation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// jshint unused: true, undef:true
define(['jquery', 'core/config', 'core/log'], function($, config, log) {

    var currentcourseid;

    /**
     * SectionControl class.
     *
     * @param {String} selector The selector for the page region containing the actions panel.
     */
    return {

        init: function(args) {

            // Attach togglestate handler to all handles in page.
            $('.local-my-cat-collapse').on('click', this.togglecatstate);
            log.debug('Local my cat control initialized');

            currentcourseid = args;
        },

        togglecatstate: function(e) {

            e.stopPropagation();
            e.preventDefault();
            var that = $(this);

            regex = /local-my-cathandle-([0-9]+)/;
            matchs = regex.exec(that.attr('id'));
            if (!matchs) {
                return;
            }
            catid = parseInt(matchs[1]);

            log.debug('Working for cat ' + catid);

            url = config.wwwroot + '/local/my/ajax/stateregister.php?';
            url += 'item=authoredcat';
            url += '&catid=' + catid;

            handlesrc = $('.block_my_authored_courses #local-my-cathandle-' + catid + ' > img').attr('src');

            if ($('.block_my_authored_courses .local-my-course.cat-' + catid).first().hasClass('collapsed')) {
                $('.block_my_authored_courses .local-my-course.cat-' + catid).removeClass('collapsed');
                handlesrc = handlesrc.replace('expanded', 'collapsed');
                $('#local-my-cathandle-' + catid).attr('src', handlesrc);
                hide = 1;
            } else {
                $('.block_my_authored_courses .local-my-course.cat-' + catid).addClass('collapsed');
                handlesrc = handlesrc.replace('collapsed', 'expanded');
                $('.block_my_authored_courses .local-my-course.cat-' + catid).attr('src', handlesrc);
                hide = 0;
            }

            url += '&hide=' + hide;

            $.get(url, function(data) {
            });

            return false;
        },

});
