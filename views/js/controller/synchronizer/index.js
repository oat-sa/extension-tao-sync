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
    'core/dataProvider/request',
    'util/url',
    'taoTaskQueue/model/taskQueueModel',
    'layout/loading-bar'
], function ($, _, moment, request, urlHelper, taskQueueModelFactory, loadingBar) {
    'use strict';

    /**
     * TAO Extension and API of the taskQueue
     * @type {Object}
     */
    var tq = {
        api: 'TaskQueueWebApi',
        ext: 'taoTaskQueue'
    };

    /**
     * Urls of webservices
     * @type {Object}
     */
    var webservices = {
        get: urlHelper.route('get', tq.api, tq.ext),
        archive: urlHelper.route('archive', tq.api, tq.ext),
        all: urlHelper.route('getAll', tq.api, tq.ext),
        download: urlHelper.route('download', tq.api, tq.ext),
        lastTask: urlHelper.route('lastTask', 'Synchronizer', 'taoSync')
    };

    /**
     * Task Label
     * @type {String}
     */
    var taskLabel = 'Data Synchronization';

    /**
     * Initialize the application
     */
    return {
        start: function start() {
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
            var $launchButton = $form.find('button[data-control="launch"]');

            /**
             * Spinners
             */
            var $spinner = $form.find('.feedback-info .icon-loop');

            /**
             * Start and update time
             */
            var timeFields = {
                $enqueued: $form.find('.enqueue-time'),
                $updated: $form.find('.update-time'),
                $completed: $form.find('.complete-time')
            };

            /**
             * Dynamic messages in the feedback boxes. These are based on the `data-type` elements
             * and stored in the format msg.$foo to indicate that $foo is a jquery element.
             */
            var msg = (function () {
                var _msg = {
                    $all: $form.find('.msg')
                };
                _msg.$all.each(function () {
                    var $currentMsg = $(this);
                    _msg['$' + $currentMsg.data('type')] = $currentMsg;
                });
                return _msg;
            }());

            /**
             * Task Queue object
             */
            var taskQueue = taskQueueModelFactory({
                url: {
                    get: webservices.get,
                    archive: webservices.archive,
                    all: webservices.all,
                    download: webservices.download
                },
                pollSingleIntervals: [
                    {iteration: Number.MAX_SAFE_INTEGER, interval: 2000}
                ],
                pollAllIntervals: [
                    {iteration: 1, interval: 8000},
                    {iteration: 0, interval: 5000}
                ]
            }).on('pollSingleFinished', function (taskId, taskData) {
                if (taskData.status === 'completed') {
                    setState('success');
                    updateTime(taskData);
                    setHistoryTime(taskData.updatedAt, '$completed');
                }
                else if (taskData.status === 'failed') {
                    setState('error');
                }
            }).on('pollSingle', function (taskId, taskData) {
                updateTime(taskData);
            }).on('error', function () {
                setState('error');
            });

            /**
             * Get task parameters
             *
             * @returns {Object}
             */
            function getData() {
                var data = {
                    label: taskLabel
                };
                $formFields.each(function () {
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
                $formFields.each(function () {
                    isValid = this.validity.valid;
                    return isValid;
                });
                if (isValid) {
                    $launchButton.removeAttr('disabled');
                } else {
                    $launchButton.attr('disabled', 'disabled');
                }
            }

            /**
             * Set the state to progress|success|error
             *
             * @param {String} state
             */
            function setState(state) {
                $container.removeClass(function (index, className) {
                    return (className.match(/(^|\s)state-\S+/g) || []).join(' ');
                });
                // make sure spinner doesn't use unnecessary resources
                $spinner[state === 'progress' ? 'addClass' : 'removeClass']('spinner-icon');
                $container.addClass('state-' + state);
                msg.$all.hide();
            }

            /**
             * Update the displayed times
             * @param {Object} taskData
             */
            function updateTime(taskData) {
                setTime(taskData.createdAt, '$enqueued');
                setTime(taskData.createdAt + taskData.createdAtElapsed, '$updated');
            }

            /**
             * Display time in a localized format
             *
             * @param {Number} timestamp
             * @param {String} type
             */
            function setTime(timestamp, type) {
                timeFields[type] && timeFields[type].text(moment.unix(timestamp).format('LTS'));
                msg[type] && msg[type].show();
            }

            /**
             * Display history time in a localized format
             *
             * @param {Number} timestamp
             * @param {String} type
             */
            function setHistoryTime(timestamp, type) {
                setTime(timestamp, type);
                $container.addClass('history');
            }

            // avoids unwanted flicker caused by the late loading of the CSS
            $container.find('.fb-container').removeClass('viewport-hidden');
            loadingBar.start();

            // check if all form fields are valid, if applicable
            $formFields.on('keyup paste blur', toggleLaunchButtonState);

            // there might be no form fields at all
            // or they might have received valid entries by other means
            toggleLaunchButtonState();

            // set form actions
            $form.on('submit', function (e) {
                e.preventDefault();
                setState('progress');
                taskQueue.create(this.action, getData());
            });
            $form.find('button[data-control="close"]').on('click', function (e) {
                e.preventDefault();
                setState('form');
            });

            request(webservices.lastTask)
                .then(function (currentTask) {
                    if (currentTask && currentTask.status) {
                        switch (currentTask.status) {
                            case 'failed':
                                setState('error');
                                break;
                            case 'completed':
                                setState('form');
                                updateTime(currentTask);
                                setHistoryTime(currentTask.updatedAt, '$completed');
                                break;
                            default:
                                setState('progress');
                                updateTime(currentTask);
                                taskQueue.pollSingle(currentTask.id);
                        }
                    }
                    else {
                        setState('form');
                    }
                    loadingBar.stop();
                })
                .catch(function () {
                    setState('error');
                });
        }
    };
});
