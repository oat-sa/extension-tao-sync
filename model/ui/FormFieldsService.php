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

use oat\oatbox\service\ConfigurableService;
use oat\taoSync\model\synchronizer\custom\byOrganisationId\testcenter\TestCenterByOrganisationId;

/**
 * Configure form fields for the data synchronization page
 *
 * @example
 * 'org-id' => [                             // used as `id` and `name` if not stated otherwise in attributes
 *     'element' => 'input',                 // node name
 *     'attributes' => [                     // anything HTML allows
 *         'required' => true,               // set `required`, `readonly` etc. to boolean values
 *         'id' => 'foo'                     // overrides usage of `org-id` (the array key)
 *         'minlength' => 5                  // HTML validation API integrated,
 *         'type' => 'search'                // defaults to `text` if omitted
 *     ],
 *     'label' => 'Organization ID'          // label text, will NOT use __()!
 * ]
 *
 * Above config will render to:
 *
 * <label for="org-id">Organization ID
 *   <abbr title="Required field">*</abbr>   // due to `required` attribute, will use __()
 * </label>
 * <input required name="org-id" id="org-id" type="text">
 *
 * @see vendor/oat-sa/jig/Utils/Element.md for more exmamples
 *
 */
class FormFieldsService extends ConfigurableService
{
    const SERVICE_ID = 'taoSync/formFields';

    const OPTION_INPUT = 'fields';

    /**
     * Retrieve custom form fields to display synchronisation form
     *
     * @return array
     */
    public function getFormFields()
    {
        $defaults   = [
            'element'    => 'input',
            'attributes' => []
        ];
        $values = \common_session_SessionManager::getSession()->getUser()->getPropertyValues(TestCenterByOrganisationId::ORGANISATION_ID_PROPERTY);
        if (count($values) > 0){
            $organizationId = $values[0];
        }
        $formFields = (array) $this->getOption(self::OPTION_INPUT);

        foreach($formFields as $key => &$formField){
            $formField = array_merge($defaults, $formField);
            if(empty($formField['attributes']['name'])){
                $formField['attributes']['name'] = $key;

                if ($organizationId){
                    $formField['attributes']['disabled'] = 'disabled';
                    $formField['attributes']['value'] = $organizationId;
                }
            }
            if(empty($formField['attributes']['id'])){
                $formField['attributes']['id'] = $key;
            }
        }

        return $formFields;
    }
}