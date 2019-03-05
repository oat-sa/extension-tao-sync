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
    'core/taskQueue/taskQueueModel',
    'layout/loading-bar',
    'taoSync/component/terminateExecutions/terminateExecutions'
], function ($, _, moment, request, urlHelper, taskQueueModelFactory, loadingBar, terminateExecutionsDialogFactory) {
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
        lastTask: urlHelper.route('lastTask', 'Synchronizer', 'taoSync'),
        activeSessions: urlHelper.route('activeSessions', 'Synchronizer', 'taoSync'),
        terminateExecutions: urlHelper.route('terminateExecutions', 'TerminateExecution', 'taoSync'),
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
             * Synchronization form
             */
            var $syncForm = $container.find('form.sync-form');

            /**
             * Terminate active sessions form
             */
            var $terminateForm = $container.find('form.sync-form');

            /**
             * Form fields, if any.
             * Note that `:input` would include the button which is not wanted.
             * Configured in config/taoSync/syncFormFields.conf.php
             */
            var $syncFormFields = $syncForm.find('input, select');

            /**
             * Launch button
             */
            var $launchButton = $syncForm.find('button[data-control="launch"]');

            /**
             * Spinners
             */
            var $spinner = $syncForm.find('.feedback-info .icon-loop');

            /**
             * Start and update time
             */
            var timeFields = {
                $enqueued: $syncForm.find('.enqueue-time'),
                $updated: $syncForm.find('.update-time'),
                $completed: $syncForm.find('.complete-time')
            };

            /**
             * Dynamic messages in the feedback boxes. These are based on the `data-type` elements
             * and stored in the format msg.$foo to indicate that $foo is a jquery element.
             */
            var msg = (function () {
                var _msg = {
                    $all: $syncForm.find('.msg')
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
                $syncFormFields.each(function () {
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
                $syncFormFields.each(function () {
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
            $syncFormFields.on('keyup paste blur', toggleLaunchButtonState);

            // there might be no form fields at all
            // or they might have received valid entries by other means
            toggleLaunchButtonState();

            // set form actions
            $syncForm.on('submit', function (e) {
                var action = this.action;
                e.preventDefault();
                setState('progress');
                $container.removeClass('active');
                request(webservices.activeSessions)
                    .then(function (data) {
                         if(Array.isArray(data.activeSessionsData) && data.activeSessionsData.length > 0) {
                             var $terminateActionContainer = $container.find('.terminate-action');
                             var $syncActionContainer = $container.find('.sync-action');
                             var dialogConfig = {
                                 data: data.activeSessionsData,
                                 csrfToken: data.token,
                                 terminateUrl: webservices.terminateExecutions
                             };

                             terminateExecutionsDialogFactory($terminateActionContainer, dialogConfig)
                                 .on('dialogRendered', function () {
                                     $syncActionContainer.toggle();
                                     $terminateActionContainer.toggle();
                                 })
                                 .on('terminationCanceled', function () {
                                     $terminateActionContainer.toggle();
                                     setState('form');
                                     $syncActionContainer.toggle();
                                 })
                                 .on('terminationFailed', function () {
                                     
                                 })
                                 .on('terminationSucceeded', function () {
                                     $terminateActionContainer.toggle();
                                     $syncActionContainer.toggle();
                                     taskQueue.create(action, getData());
                                 })
                                 .render($terminateActionContainer);
                         } else {
                             taskQueue.create(action, getData());
                         }
                    })
                    .catch(function () {
                        setState('error');
                    });
            });
            $syncForm.find('button[data-control="close"]').on('click', function (e) {
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
