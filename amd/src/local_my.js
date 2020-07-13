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
            $('#mydashboard .block').on('click', '.local-my-cat-collapse', [], this.toggle_cat_state);
            $('#mydashboard .block').on('change', '.local-my-modality-chooser', [], this.toggle_modality);
            $('#mydashboard .block').on('click', '.local-my-area-ctls', [], this.global_area_ctl);
            $('#mydashboard .block').on('click', '.detail-handle', [], this.toggle_detail);
            $('#mydashboard .block').on('click', '.add-to-favorites-handle', [], this.add_to_favorites);
            $('#mydashboard .block').on('click', '.remove-from-favorites-handle', [], this.remove_from_favorites);

            $('#mydashboard .block').on('click', '.course-filter', [], this.refresh_course_list);
            $('#mydashboard .block').on('click', '.course-sort', [], this.refresh_course_list);
            $('#mydashboard .block').on('click', '.course-display', [], this.refresh_course_list);
            $('#mydashboard .block').on('click', '.course-time', [], this.refresh_course_list);
            $('#mydashboard .block').on('click', '.reload-areas', [], this.refresh_course_list);

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

        // Refreshes all handler bindings when something is reloaded.
        postreset: function() {

            if ($('.is-accordion').length !== 0) {
                // Is in accordion
                $('.is-accordion .local-my-course').hide();
                $('.is-accordion .local-my-cat-collapse > h3 > a').attr('aria-expanded', 'false');
            }
        },

        hide_home_nav: function() {
            $('a[data-key="home"]').css('display', 'none');
        },

        toggle_cat_state: function(e) {

            e.stopPropagation();
            e.preventDefault();
            var that = $(this);

            var regex = /local-my-cathandle-([^-]+)-([0-9]+)/;
            var matchs = regex.exec(that.attr('id'));
            if (!matchs) {
                return;
            }
            var area = matchs[1];
            var catid = parseInt(matchs[2]);

            log.debug('Working for cat ' + catid + ' in area ' + area);

            if (that.closest('.is-accordion').length === 0) {
                // This is the previous close/open mode.
                var url = cfg.wwwroot + '/local/my/ajax/stateregister.php?';
                url += 'item=' + area;
                url += '&catid=' + catid;

                var hide = 0;

                if ($('.local-my-course-' + area + '.cat-' + area + '-' + catid).first().hasClass('collapsed')) {
                    $('.local-my-course-' + area + '.cat-' + area + '-' + catid).removeClass('collapsed');
                    $('#local-my-cathandle-' + area + '-' + catid + ' > h3 > a').attr('aria-expanded', 'true');
                    log.debug('Expanding ' + area + ' in area ' + catid);
                    hide = 0;
                } else {
                    $('.local-my-course-' + area + '.cat-' + area + '-' + catid).addClass('collapsed');
                    $('#local-my-cathandle-' + area + '-' + catid + ' > h3 > a').attr('aria-expanded', 'false');
                    log.debug('Closing ' + area + ' in area ' + catid);
                    hide = 1;
                }

                url += '&hide=' + hide;

                $.get(url, function() {
                });

            } else {
                // This is the accordion mode.
                $('.local-my-course-' + area).slideUp("normal");
                $('.local-my-cat-collapse-' + area + ' > h3 > a').attr('aria-expanded', 'false');
                $('.local-my-course-' + area + '.cat-' + area + '-' + catid).slideDown("normal");
                $('#local-my-cathandle-' + area + '-' + catid + ' > h3 > a').attr('aria-expanded', 'true');
            }

            return false;
        },

        /**
         * When administrator and using static text modules based on profile fields
         */
        toggle_modality: function() {

            var that = $(this);

            var modalityid = that.attr('id').replace('local-my-static-select-', 'local-my-static-modal-');
            $('.local-my-statictext-modals').addClass('local-my-hide');
            $('#' + modalityid + '-' + that.val()).removeClass('local-my-hide');
        },

        toggle_detail: function(e) {

            e.stopPropagation();
            e.preventDefault();
            var that = $(this);

            var courseid = that.attr('id').replace('detail-handle-', '');
            var panelid = '#details-indicators-' + courseid;
            if ($(panelid).css('display') === 'none') {
                $(panelid).css('display', 'flex');
                that.attr('aria-expanded', true);
                that.children('i').removeClass('fa-caret-down');
                that.children('i').addClass('fa-caret-up');
            } else {
                $(panelid).css('display', 'none');
                that.attr('aria-expanded', false);
                that.children('i').removeClass('fa-caret-up');
                that.children('i').addClass('fa-caret-down');
            }

            return false;
        },

        global_area_ctl: function(e) {

            var that = $(this);
            e.stopPropagation();
            e.preventDefault();

            var regexp = /local-my-cats-([^-]+)-([^-]+)$/;
            var matches = that.attr('id').match(regexp);
            if (!matches) {
                return;
            }

            var mode = matches[1];
            var area = matches[2];
            var url = '';

            if (mode == 'collapseall') {
                $('.local-my-course-' + area).addClass('collapsed');
                $('.local-my-cat-collapse-' + area + ' > h3 > button > img').each( function(index, element) {
                    var handlesrc = element.src;
                    handlesrc = handlesrc.replace('expanded', 'collapsed');
                    element.src = handlesrc;
                });
                url = cfg.wwwroot + '/local/my/ajax/stateregister.php?';
                url += 'item=' + area;
                url += '&catids=' + $('#local-my-areacategories-' + area).html();
                url += '&what=collapseall';

                $.get(url);
            } else {
                $('.local-my-course-' + area).removeClass('collapsed');
                $('.local-my-cat-collapse-' + area + ' > h3 > button > img').each( function(index, element) {
                    var handlesrc = element.src;
                    handlesrc = handlesrc.replace('collapsed', 'expanded');
                    element.src = handlesrc;
                });

                url = cfg.wwwroot + '/local/my/ajax/stateregister.php?';
                url += 'item=' + area;
                url += '&catids=' + $('#local-my-areacategories-' + area).html();
                url += '&what=expandall';

                $.get(url);
            }

            return false;
        },

        sektor: function(args) {

            if (!('color' in args)) {
                args['color'] = '#bb3030';
            }

            if (!('circlecolor' in args)) {
                args['circlecolor'] = '#ddd';
            }

            /* eslint-disable */
            var sektor = new Sektor(args['id'], {
              size: args['size'],
              stroke: 0,
              arc: false,
              angle: args['angle'],
              sectorColor: args['color'],
              circleColor: args['circlecolor'],
              fillCircle: true
            });
            /* eslint-enable */
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
            var favorites = $('.favorite-courses');
            if (favorites.length) {
                favorites.each(function() {
                    // find the reload-areas direct child and apply reload.
                    $(this).children('.reload-areas').trigger('click');
                });
            }
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
            var favorites = $('.favorite-courses');
            if (favorites.length) {
                favorites.each(function() {
                    // find the reload-areas direct child and apply reload.
                    $(this).children('.reload-areas').trigger('click');
                });
            }
        },

        /**
         * finds widget UID in sort or filter and refresh content of the whole widget content.
         */
        refresh_course_list: function() {
            var that = $(this);

            // Collect filter and sort state.
            var uid = that.attr('data-uid');
            var view = that.attr('data-view');
            var widget = that.attr('data-widget');

            log.debug("Activating sort/filter/display option on " + uid);

            var filters = $('.course-filters-' + uid + '.active');

            var url = cfg.wwwroot + '/local/my/ajax/service.php';
            url += '?what=getcourses';
            url += '&widget=' + widget;
            url += '&uid=' + uid;
            url += '&view=' + view;
            if (!that.attr('data-sort')) {
                var activesort = $('.course-sort-' + uid + '.active');
                if (activesort) {
                    // Add sort if exists.
                    url += '&sort=' + activesort.attr('data-sort');
                }
            } else {
                url += '&sort=' + that.attr('data-sort');
            }

            if (!that.attr('data-display')) {
                var activedisplay = $('.course-display-' + uid + '.active');
                if (activedisplay) {
                    // Add display if exists.
                    url += '&display=' + activedisplay.attr('data-display');
                }
            } else {
                url += '&display=' + that.attr('data-display');
            }

            if (!that.attr('data-time')) {
                var activetime = $('.course-time-' + uid + '.active');
                if (activetime) {
                    // Add time selector if exists.
                    url += '&schedule=' + activetime.attr('data-time');
                }
            } else {
                url += '&schedule=' + that.attr('data-time');
            }

            // Process if clicked item was a filter option.
            var currentdatafilter;
            if (that.attr('data-filter')) {
                currentdatafilter = that.attr('data-filter');
                url += '&' + that.attr('data-filter') + '=' + that.attr('data-filter-value');
            }

            if (filters.length) {
                // Add all filters of the widget. Avoid contradicting a clicked filter.
                for (var filter in filters) {
                    if (filter.attr('data-filter') != currentdatafilter) {
                        url += '&' + filter.attr('data-filter') + '=' + filter.attr('data-filter-value');
                    }
                }
            }

            if (localmy.usereloadwaiter) {
                $('#content-' + uid).html('<img src="' + cfg.wwwroot + '/pix/i/loading.gif">');
            }

            $.get(url, function(data) {
                $('#area-courses-' + uid).html(data);
            }, 'html');
        }
    };

    return localmy;

});
