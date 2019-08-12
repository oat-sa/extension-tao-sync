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
 * @author Anton Tsymuk <anton@taotesting.com>
 */
define([
    'i18n',
    'lodash',
    'core/eventifier',
    'core/promise',
    'ui/dashboard',
    'core/dataProvider/request',
    'util/url',
], function (__, _, eventifier, Promise, dashboard, request, urlHelper) {
    'use strict';

    /**
     * Components that trigger readiness checks and display the results of the checks
     *
     * @param {Object} $container
     * @returns {readinessDashboard}
     */
    function readinessDashboardFactory($container) {
        /**
         * @typedef {readinessDashboardFactory}
         */
        return eventifier({
            performChecks: function performChecks() {
                var self = this;

                var checksPromise = new Promise(function (resolve) {
                    request(urlHelper.route('getMachineChecks', 'Synchronizer', 'taoSync')).then(
                        function(data) {
                            resolve(data);
                        }
                    );
                });

                checksPromise.then(function (data) {
                    self.dashboard.toggleLoadingBar(false);
                    self.dashboard.renderMetrics(data);
                });
            },
            render: function render() {
                this.dashboard = dashboard({
                    renderTo: $container,
                    loading: true,
                    warningText: false,
                    layoutType: 'list'
                });

                this.performChecks();

                return this;
            },
            destroy: function destroy() {
                if (this.dashboard) {
                    this.dashboard.destroy();
                }

                this.trigger('destroy');

                return this;
            }
        });
    }

    return readinessDashboardFactory;
});
