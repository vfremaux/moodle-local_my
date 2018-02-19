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
            $('.local-my-cat-collapse').bind('click', this.toggle_cat_state);
            $('#id-modalities-chooser').bind('change', this.toggle_modality);
            $('.local-my-area-ctls').bind('click', this.global_area_ctl);

            log.debug('AMD Local my cat control initialized');

            currentcourseid = args;
        },

        toggle_cat_state: function(e) {

            e.stopPropagation();
            e.preventDefault();
            var that = $(this);

            regex = /local-my-cathandle-([^-]+)-([0-9]+)/;
            matchs = regex.exec(that.attr('id'));
            if (!matchs) {
                return;
            }
            area = matchs[1];
            catid = parseInt(matchs[2]);

            log.debug('Working for cat ' + catid + ' in area ' + area);

            url = config.wwwroot + '/local/my/ajax/stateregister.php?';
            url += 'item=' + area;
            url += '&catid=' + catid;

            handlesrc = $('#local-my-cathandle-' + area + '-' + catid).attr('src');

            if ($('.local-my-course-' + area + '.cat-' + area + '-' + catid).first().hasClass('collapsed')) {
                $('.local-my-course-' + area + '.cat-' + area + '-' + catid).removeClass('collapsed');
                handlesrc = handlesrc.replace('collapsed', 'expanded');
                $('#local-my-cathandle-' + area + '-' + catid).attr('src', handlesrc);
                hide = 0;
            } else {
                $('.local-my-course-' + area + '.cat-' + area + '-' + catid).addClass('collapsed');
                handlesrc = handlesrc.replace('expanded', 'collapsed');
                $('#local-my-cathandle-' + area + '-' + catid).attr('src', handlesrc);
                hide = 1;
            }

            url += '&hide=' + hide;

            $.get(url, function(data) {
            });

            return false;
        },

        /**
         * When administrator and using static text modules based on profile fields
         */
        toggle_modality: function() {
            that = $(this);

            modalityid = that.attr('id').replace('local-my-static-select-', 'local-my-static-modal-');
            $('.local-my-statictext-modals').addClass('local-my-hide');
            $('#' + modalityid).removeClass('local-my-hide');
        },

        global_area_ctl: function(e) {

            that = $(this);
            e.stopPropagation();
            e.preventDefault();

            regexp = /local-my-cats-([^-]+)-([^-]+)$/;
            matches = that.attr('id').match(regexp);
            if (!matches) {
                return;
            }

            var mode = matches[1];
            var area = matches[2];

            if (mode == 'collapseall') {
                $('.local-my-course-' + area).addClass('collapsed');
                $('.local-my-cat-collapse-' + area).each( function(index, element) {
                    var handlesrc = element.src;
                    handlesrc = handlesrc.replace('expanded', 'collapsed');
                    element.src = handlesrc;
                });
                url = config.wwwroot + '/local/my/ajax/stateregister.php?';
                url += 'item=' + area;
                url += '&catids=' + $('#local-my-areacategories-' + area).html();
                url += '&what=collapseall';

                $.get(url);
            } else {
                $('.local-my-course-' + area).removeClass('collapsed');
                $('.local-my-cat-collapse-' + area).each( function(index, element) {
                    var handlesrc = element.src;
                    handlesrc = handlesrc.replace('collapsed', 'expanded');
                    element.src = handlesrc;
                });

                url = config.wwwroot + '/local/my/ajax/stateregister.php?';
                url += 'item=' + area;
                url += '&catids=' + $('#local-my-areacategories-' + area).html();
                url += '&what=expandall';

                $.get(url);
            }

            return false;
        }
    };

});
