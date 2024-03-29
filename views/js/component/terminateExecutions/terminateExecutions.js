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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA ;
 */

/**
 * @author Oleksandr Khitko <oleksandr@taotesting.com>
 */
define([
    'i18n',
    'lodash',
    'core/request',
    'ui/component',
    'tpl!taoSync/component/terminateExecutions/notification',
], function (__, _, request, component, listTpl) {
    'use strict';

    /**
     * Component that lists all the executions which are not in completed state and allows to terminate them
     *
     * @param {Object} config
     * @returns {terminateExecutionsDialog}
     */
    function terminateExecutionsDialogFactory($container, config) {
        var terminateExecutionsDialog;

        if (!_.isPlainObject(config)) {
            throw new TypeError('The configuration is required');
        }

        function terminateDeliveryExecutions() {
            var data = {
                executionsId: config.activeExecutions
            };

            return request({
                url: config.terminateUrl,
                data: data,
                method: 'POST',
                noToken: false
              });
        }

        /**
         * @typedef {terminateExecutionsDialog}
         */
        return component({})
            .setTemplate(listTpl)
            .on('init', function() {
                config.activeExecutions = [];
                config.aggregatedData = {};

                config.notificationMessage = (config.data.length > 1
                    ? __('There are %s assessments in progress', config.data.length)
                    : __('There is one assessment in progress'))
                + ', ' + __('please ensure that everyone has completed the assessment before proceeding.');

                _.forEach(config.data, function (execution) {
                    config.activeExecutions.push(execution.execution_id);

                    config.aggregatedData[execution.context_id] = config.aggregatedData[execution.context_id] || {"total": 0};
                    config.aggregatedData[execution.context_id]['label'] = execution.label;
                    config.aggregatedData[execution.context_id]['total']++;
                });

                this.config = config;
            }).on('render', function() {
                var self = this;

                // Cancel button handler
                $container.find('.cancel-button').on('click', function (e) {
                    e.preventDefault();
                    self.trigger('terminationCanceled');
                    self.destroy();
                });

                // Force and terminate button handler
                $container.find('.force-terminate-button').on('click', function (e) {
                    e.preventDefault();
                    terminateDeliveryExecutions()
                        .then(function (response) {
                            self.trigger('terminationSucceeded');
                            self.destroy();
                        })
                        .catch(function (error) {
                            self.trigger('terminationFailed');
                        });
                });

                self.trigger('dialogRendered');
            })
            .init(config);
    }

    return terminateExecutionsDialogFactory;
});
