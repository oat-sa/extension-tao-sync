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
    'taoTaskQueue/model/taskQueueModel',
    'layout/loading-bar'
], function($, _, moment, urlHelper, taskQueueModelFactory, loadingBar) {
    'use strict';

    /**
     * TAO Extension and API of the taskQueue
     * @type {{api: string, ext: string}}
     */
    var tq = {
        api: 'TaskQueueWebApi',
        ext: 'taoTaskQueue'
    };

    /**
     * Task Label
     * @type {string}
     */
    var taskLabel = 'Data Synchronization';

    /**
     * Container
     */
    var $container = $('#tao-sync-container');

    /**
     * Form
     */
    var $form = $container.find('form');

    /**
     * Form fields, if any.
     * Note that `:input` would include the button which is not wanted.
     * Configured in config/taoSync/syncFormFields.conf.php
     */
    var $formFields = $form.find('input, select');

    /**
     * Launch button
     */
    var $launchButton = $form.find('button');

    /**
     * Spinners
     */
    var $spinner = $form.find('.feedback-info .icon-loop');

    /**
     * Start and update time
     */
    var timeFields = {
        $enqueued: $form.find('#enqueue-time'),
        $updated:  $form.find('#update-time')
    };

    /**
     * Dynamic messages in the feedback boxes. These are based on the `data-type` elements
     * and stored in the format msg.$foo to indicate that $foo is a jquery element.
     */
    var msg = (function() {
        var _msg = {
            $all: $form.find('.msg')
        };
        _msg.$all.each(function() {
            var $currentMsg = $(this);
            _msg['$' + $currentMsg.data('type')] = $currentMsg;
        });
        return _msg;
    }());

    /**
     * Task Queue object
     */
    var taskQueue = taskQueueModelFactory({
        url : {
            get: urlHelper.route('get', tq.api, tq.ext),
            archive: urlHelper.route('archive', tq.api, tq.ext),
            all : urlHelper.route('getAll', tq.api, tq.ext),
            download : urlHelper.route('download', tq.api, tq.ext)
        },
        pollSingleIntervals : [
            {iteration: Number.MAX_SAFE_INTEGER, interval: 2000}
        ],
        pollAllIntervals : [
            {iteration: 1, interval: 8000},
            {iteration: 0, interval: 5000}
        ]
    }).on('pollSingleFinished', function(taskId, taskData) {
        if(taskData.status === 'completed') {
            taskQueue.archive(taskData.id).then(function () {
                taskQueue.pollAll();
                setState('success');
            });
            setState('success');
        }
        else if(taskData.status === 'failed'){
            setState('error');
        }
    }).on('pollSingle', function(taskId, taskData){
        setTime(taskData.createdAt, '$enqueued');
        msg.$enqueued.show();
        setTime(taskData.createdAt + taskData.createdAtElapsed, '$updated');
        msg.$updated.show();
    }).on('error', function(){
        setState('error');
    });

    /**
     * Get task parameters
     *
     * @returns {{}}
     */
    function getData() {
        var data = {
            label: taskLabel
        };
        $formFields.each(function() {
            data[this.name] = this.value;
        });
        return data;
    }

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
     * Set the state to progress|success|error
     *
     * @param state
     */
    function setState(state) {
        $container.removeClass (function (index, className) {
            return (className.match (/(^|\s)state-\S+/g) || []).join(' ');
        });
        // make sure spinner doesn't use unnecessary resources
        $spinner[state === 'progress' ? 'addClass' : 'removeClass']('spinner-icon');
        $container.addClass('state-' + state);
    }


    /**
     * Display time in a localized format
     *
     * @param timestamp
     * @param type
     */
    function setTime(timestamp, type) {
        timeFields[type].text(moment.unix(timestamp).format('LTS'));
    }


    /**
     * Initialize the application
     */
    function init() {
        // avoids unwanted flicker caused by the late loading of the CSS
        $container.find('.fb-container').removeClass('viewport-hidden');
        loadingBar.start();

        taskQueue.getAll().then(function(taskList) {
            var currentTask;
            var i = taskList.length;
            var validStates = ['created','in_progress','completed','failed'];
            while(i--){
                if(taskList[i].taskLabel === taskLabel && validStates.indexOf(taskList[i].status) > -1) {
                    currentTask = taskList[i];
                    break;
                }
            }

            if(currentTask) {
                msg.$all.hide();
                switch(currentTask.status) {
                    case 'failed':
                        setState('error');
                        break;
                    case 'completed':
                        setState('success');
                        break;
                    default:
                        msg.$progress.show();
                        setState('progress');
                        setTime(currentTask.createdAt, '$enqueued');
                        taskQueue.pollSingle(currentTask.id);
                }
                loadingBar.stop();
            }
            else {
                setState('form');
                loadingBar.stop();

                // check if all form fields are valid, if applicable
                $formFields.on('keyup paste blur', toggleLaunchButtonState);

                // there might be no form fields at all
                // or they might have received valid entries by other means
                toggleLaunchButtonState();

                $form.on('submit', function(e) {
                    e.preventDefault();
                    taskQueue.pollAllStop();
                    msg.$all.hide();
                    msg.$progress.show();
                    setState('progress');
                    taskQueue.create(this.action, getData()).then(function (result) {
                        if (result.finished) {
                            taskQueue.archive(result.task.id).then(function () {
                                taskQueue.pollAll();
                                setState('success');
                            });
                            setState('success');
                        }
                        else {
                            setTime(result.task.createdAt, '$enqueued');
                            msg.$enqueued.show();
                            setState('progress');
                        }
                    });
                });
            }
        });
    }

    init();
});
