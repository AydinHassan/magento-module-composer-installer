<?php

/*
 * Copyright 2011 Daniel Sloof
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Danslo_ApiImport_Model_Import_Entity_Customer_Address
    extends Mage_ImportExport_Model_Import_Entity_Customer_Address
{

    /**
     * Makes sure address model is using the proper data source model.
     *
     * @param Mage_ImportExport_Model_Import_Entity_Customer $customer
     */
    public function __construct(Mage_ImportExport_Model_Import_Entity_Customer $customer)
    {
        parent::__construct($customer);
        $this->_dataSourceModel = Danslo_ApiImport_Model_Import::getDataSourceModel();
    }

}
