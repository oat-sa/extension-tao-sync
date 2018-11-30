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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Oleksandr Khitko <oleksandr@taotesting.com>
 */
define([
    'jquery',
    'i18n',
    'lodash',
    'uri',
    'core/promise',
    'ui/component',
    'tpl!taoSync/component/synchronization/list',
    'ui/datatable'
], function ($, __, _, uri, Promise, component, listTpl) {
    'use strict';

    /**
     * Component that lists all the synchronization history entry points for a particular user
     *
     * @param {Object} config
     * @returns {syncHistoryList}
     */
    function syncHistoryListFactory(config) {
        var syncHistoryList;

        if (!_.isPlainObject(config)) {
            throw new TypeError('The configuration is required');
        }

        if (!_.isPlainObject(config.model) && !_.isArray(config.model)) {
            throw new TypeError('The data model is required');
        }


        /**
         *
         * @typedef {syncHistoryList}
         */
        syncHistoryList = component({});
        syncHistoryList.on('render', function() {
            this.getElement().datatable({
                sortby: config.sortby,
                sortorder: config.sortorder,
                url: config.dataUrl,
                model: config.model,
                actions: config.actions
            });
        }).setTemplate(listTpl);

        return syncHistoryList.init();
    }

    return syncHistoryListFactory;
});
