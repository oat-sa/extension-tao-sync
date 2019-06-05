<?php
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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoSync\model\ui;

use oat\generis\model\user\UserRdf;
use oat\tao\helpers\Template;
use oat\taoSync\controller\SyncManagerTree;
use oat\taoSync\model\SyncService;
use oat\taoTestCenter\model\gui\form\formFactory\FormFactory;

/**
 * Class SyncUserFormFactory
 *
 * Generate a form for assignation of SyncManager to test center
 *
 * @package oat\taoSync\model\ui
 */
class SyncUserFormFactory extends FormFactory
{
    /**
     * Set the form url according to SyncManagerTree
     *
     * @param \core_kernel_classes_Resource $testCenter
     * @return mixed
     */
    public function __invoke(\core_kernel_classes_Resource $testCenter)
    {
        /** @var tao_helpers_form_GenerisTreeForm $form */
        $form = parent::__invoke($testCenter);
        $form->setTemplate(
            Template::getTemplate('form' . DIRECTORY_SEPARATOR . 'generis_tree_form.tpl', 'taoSync')
        );
        $form->setData('saveUrl', _url('setReverseValues', 'SyncManagerTree', 'taoSync'));
        $form->setData('saveForcedParam', SyncManagerTree::IS_FORCED_REQUEST_PARAM_KEY);
        $form->setData(
            'dataUrl',
            _url(
                'getData',
                'GenerisTree',
                'tao',
                http_build_query(['filterProperties' => [
                    UserRdf::PROPERTY_ROLES => [SyncService::TAO_SYNC_ROLE]
                ]])
            ));
        return $form;
    }
}