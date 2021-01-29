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
// jshint unused: false, undef:false
/* eslint-disable no-undef no-unused-vars */
define(['jquery', 'core/config', 'core/log'], function($, cfg, log) {

    /**
     * SectionControl class.
     *
     * @param {String} selector The selector for the page region containing the actions panel.
     */
    var localmy = {

        usereloadwaiter: false,

        init: function() {

            // Attach delegated togglestate handler to all handles in page.
            $('#mydashboard .block').on('click', '.detail-handle', [], this.toggle_detail);
            $('#mydashboard .block').on('click', '.add-to-favorites-handle', [], this.add_to_favorites);
            $('#mydashboard .block').on('click', '.remove-from-favorites-handle', [], this.remove_from_favorites);

            if ($('.is-accordion').length !== 0) {
                // Is in accordion
                $('.is-accordion .local-my-course').hide();
                $('.is-accordion .local-my-cat-collapse > h3 > a').attr('aria-expanded', 'false');
            }

            // Launch lazy loading of all area place-holders.
            $('.reload-areas').each(function() {
                log.debug("Launching refresh on " + $(this).attr('data-widget') + '-' + $(this).attr('data-uid'));
                $(this).trigger('click');
            });
            this.usereloadwaiter = true;

            log.debug('AMD Local My initialized');

        },

        add_to_favorites: function() {

            var that = $(this);

            var courseid = that.attr('data-course');
            var islight = that.hasClass('light');

            var url = cfg.wwwroot + '/local/my/ajax/service.php';
            url += '?what=addtofavorites';
            url += '&courseid=' + courseid;
            that.removeClass('fa-star-o');
            that.addClass('fa-star');
            if (islight) {
                that.removeClass('add-to-favorites-handle');
                that.addClass('remove-from-favorites-handle');
            }

            log.debug("Adding course " + courseid + " to favorites");
            $.get(url);

            // Find and reload favorites.
            // DO NOT RELOAD FAVORITES ON COURSE INDEX
        },

        remove_from_favorites: function() {

            var that = $(this);

            var courseid = that.attr('data-course');
            var islight = that.hasClass('light');

            var url = cfg.wwwroot + '/local/my/ajax/service.php';
            url += '?what=removefromfavorites';
            url += '&courseid=' + courseid;

            log.debug("Removing course " + courseid + " from favorites");
            $.get(url);

            // find on screen icon-favorites of this course and change class.
            $('.icon-favorite[data-course="' + courseid + '"]').removeClass('fa-star');
            $('.icon-favorite[data-course="' + courseid + '"]').addClass('fa-star-o');
            if (islight) {
                that.removeClass('fa-star');
                that.addClass('fa-star-o');
                that.removeClass('remove-from-favorites-handle');
                that.addClass('add-to-favorites-handle');
            }

            // Find and reload favorites.
            // DO NOT RELOAD FAVORITES ON COURSE INDEX
        }

    };

    return localmy;

});
