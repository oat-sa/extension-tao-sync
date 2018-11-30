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
    'i18n',
    'module',
    'core/logger',
    'util/url',
    'layout/actions/binder',
    'layout/loading-bar',
    'ui/feedback',
    'tpl!taoSync/component/synchronization/detailedReport',
    'taoSync/component/synchronization/list'
], function (
    $,
    _,
    __,
    module,
    loggerFactory,
    urlHelper,
    binder,
    loadingBar,
    feedback,
    layout,
    syncHistoryListFactory
) {
    'use strict';

    var logger = loggerFactory('controller/SynchronizationHistory/index');
    var $table;

    /**
     * Take care of errors
     * @param err
     */
    function reportError(err) {
        loadingBar.stop();

        logger.error(err);

        if (err instanceof Error) {
            feedback().error(err.message);
        }
    }

    function viewReport(rowId) {
        loadingBar.start();

        $.ajax({
            url : urlHelper.route('viewReport', 'SynchronizationHistory', 'taoSync', {'id': rowId}),
            type : 'GET',
            success : function (result) {
                if (typeof result === 'object' && result !== null) {
                    Object.keys(result).forEach(function (key) {
                        if (typeof result[key] === 'object' && result[key] !== null) {}
                        result[key] = JSON.stringify(result[key], null, 1);
                    });
                }

                var $container = $(layout({data: result}));
                var datatable = $('#sync-history');
                datatable.append($container);
                $container.modal({
                    startClosed : true,
                    minWidth : 450
                });
                $container.modal('open');
                loadingBar.stop();
            },
            error : function (xhr, err) {
                var message = getRequestErrorMessage(xhr);
                feedback().error(message, {encodeHtml : false});
                loadingBar.stop();
            }
        });
    }

    return {

        /**
         * Controller entry point
         */
        start: function () {
            var model = [
                {
                    id: 'status',
                    label: __('Result'),
                    sortable: true
                },
                {
                    id: 'created_at',
                    label: __('Time'),
                    sortable: true
                },
                {
                    id: 'data',
                    label: __('Data'),
                    transform: function(value, row) {
                        return value.replace(/\n/g, '<br />');
                    }
                },
                {
                    id: 'organisation',
                    label: __('Organisation ID')
                }
            ];

            var actions = {
                'view': {
                    action: viewReport,
                    id: 'view',
                    label: __('View'),
                    title : __('Detail view'),
                    icon: 'view'
                }
            };

            var config = module.config() || {};
            var listConfig = {
                sortby: 'created_at',
                sortorder: 'desc',
                dataUrl: urlHelper.route('getHistory', 'SynchronizationHistory', 'taoSync', {}),
                model: model,
                actions: actions
            };

            $table = $('#sync-history');
            if ($table.length) {
                loadingBar.start();
                syncHistoryListFactory(listConfig, actions)
                    .on('error', reportError)
                    .on('success', function (message) {
                        feedback().success(message);
                    })
                    .before('loading', function() {
                        loadingBar.start();
                    })
                    .after('loaded', function () {
                        loadingBar.stop();
                    })
                    .render($('.sync-history-grid', $table));
            } else {
                loadingBar.stop();
            }
        }
    };
});
