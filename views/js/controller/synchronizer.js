/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2018 Open Assessment Technologies SA
 */

define([
    'jquery',
    'lodash',
    'moment',
    'util/url',
    'taoTaskQueue/model/taskQueueModel'
], function($, _, moment, urlHelper, taskQueueModelFactory) {
    'use strict';

    /**
     * Container (the grey block)
     */
    var $container = $('#tao-sync-container');

    /**
     * Form fields, if any.
     * Configured in config/taoSync/syncFormFields.conf.php
     */
    var $formFields = $container.find(':input');

    /**
     * Launch button
     */
    var $launchButton = $container.find('button');


    /**
     * Dis/enable Launch Button depending on the state of fields
     * that have any kind of HTML validator
     */
    function toggleLaunchButtonState() {
        var isValid = true;
        $formFields.each(function() {
            if(!this.validity.valid) {
                $launchButton.attr('disabled','disabled');
                isValid = false;
                return false;
            }
        });
        if(isValid) {
            $launchButton.removeAttr('disabled');
        }
    }

    /**
     * Set the state to progress|success|failure|timeout
     *
     * @param state
     */
    function setState(state) {
        $container.removeClass (function (index, className) {
            return (className.match (/(^|\s)state-\S+/g) || []).join(' ');
        });
        if(state) {
            $container.addClass('state-' + state);
        }
    }

    /**
     * Initialize the application
     */
    function init() {
        // avoids unwanted flicker caused by the late loading of the CSS
        $container.find('.messages').removeClass('viewport-hidden');

        // check if all form fields are valid, if applicable
        $formFields.on('keyup paste blur', toggleLaunchButtonState);

        // there might be no form fields at all
        // or they might have received valid entries by other means
        toggleLaunchButtonState();

        $launchButton.on('click', function() {
            var $startTime = $container.find('#start-time');
            $startTime.text(moment().format('LT'));
            setState('progress');
        });
    }

    init();


    return taskQueueModelFactory({
        url : {
            get: urlHelper.route('get', 'TaskQueueWebApi', 'taoTaskQueue'),
            archive: urlHelper.route('archive', 'TaskQueueWebApi', 'taoTaskQueue'),
            all : urlHelper.route('getAll', 'TaskQueueWebApi', 'taoTaskQueue'),
            download : urlHelper.route('download', 'TaskQueueWebApi', 'taoTaskQueue')
        },
        pollSingleIntervals : [
            {iteration: 3, interval:1000}
        ],
        pollAllIntervals : [
            {iteration: 1, interval:8000},
            {iteration: 0, interval:5000}
        ]
    });
});
