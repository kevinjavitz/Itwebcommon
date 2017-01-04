<?php
if (Mage::helper('itwebcommon')->hasPayperrentals()) {
    class ITwebexperts_Itwebcommon_Model_ImportExport_Import_Entity_Product extends Mage_ImportExport_Model_Import_Entity_Product
    {

        /**
         * Uploading files into the "catalog/product" media folder.
         * Return a new file name if the same file is already exists.
         *
         * @param string $fileName
         *
         * @return string
         */
        protected function _uploadMediaFiles($fileName)
        {
            $mediaDir = Mage::getConfig()->getOptions()->getMediaDir() . '/catalog/product';

            if (file_exists($mediaDir . $fileName)) {
                return $fileName;
            } else {
                $fileDispersedPath = '/' . strtolower(substr($fileName, 0, 1) . '/' . substr($fileName, 1,
                            1) . '/' . $fileName);
                if (file_exists($mediaDir . $fileDispersedPath)) {
                    return $fileDispersedPath;
                } else {
                    try {
                        $res = $this->_getUploader()->move($fileName);
                        return $res['file'];
                    } catch (Exception $e) {
                        return '';
                    }
                }
            }
            return '';
        }


        /**
         * Save product media gallery.
         *
         * @param array $mediaGalleryData
         * @return Mage_ImportExport_Model_Import_Entity_Product
         */
        protected function _saveMediaGallery(array $mediaGalleryData)
        {
            if (empty($mediaGalleryData)) {
                return $this;
            }

            static $mediaGalleryTableName = null;
            static $mediaValueTableName = null;
            static $productId = null;

            if (!$mediaGalleryTableName) {
                $mediaGalleryTableName = Mage::getModel('importexport/import_proxy_product_resource')
                    ->getTable('catalog/product_attribute_media_gallery');
            }

            if (!$mediaValueTableName) {
                $mediaValueTableName = Mage::getModel('importexport/import_proxy_product_resource')
                    ->getTable('catalog/product_attribute_media_gallery_value');
            }

            foreach ($mediaGalleryData as $productSku => $mediaGalleryRows) {
                $productId = $this->_newSku[$productSku]['entity_id'];
                $insertedGalleryImgs = array();
                $existingMediaValues = null;

                if (Mage_ImportExport_Model_Import::BEHAVIOR_APPEND != $this->getBehavior()) {
                    $this->_connection->delete(
                        $mediaGalleryTableName,
                        $this->_connection->quoteInto('entity_id IN (?)', $productId)
                    );
                } else {
                    $existingMediaValues = $this->_connection->fetchPairs($this->_connection->select()
                        ->from($mediaGalleryTableName, array('value', 'value_id'))
                        ->where('entity_id IN (?)', $productId)
                    );
                }

                foreach ($mediaGalleryRows as $insertValue) {
                    if (
                        is_array($existingMediaValues) &&
                        array_key_exists($insertValue['value'], $existingMediaValues)
                    ) {
                        continue;
                    }

                    if (!in_array($insertValue['value'], $insertedGalleryImgs)) {
                        $valueArr = array(
                            'attribute_id' => $insertValue['attribute_id'],
                            'entity_id' => $productId,
                            'value' => $insertValue['value']
                        );

                        $this->_connection
                            ->insertOnDuplicate($mediaGalleryTableName, $valueArr, array('entity_id'));

                        $insertedGalleryImgs[] = $insertValue['value'];
                    }

                    $newMediaValues = $this->_connection->fetchPairs($this->_connection->select()
                        ->from($mediaGalleryTableName, array('value', 'value_id'))
                        ->where('entity_id IN (?)', $productId)
                    );

                    if (array_key_exists($insertValue['value'], $newMediaValues)) {
                        $insertValue['value_id'] = $newMediaValues[$insertValue['value']];
                    }

                    $valueArr = array(
                        'value_id' => $insertValue['value_id'],
                        'store_id' => Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID,
                        'label' => $insertValue['label'],
                        'position' => $insertValue['position'],
                        'disabled' => $insertValue['disabled']
                    );

                    try {
                        $this->_connection
                            ->insertOnDuplicate($mediaValueTableName, $valueArr, array('value_id'));
                    } catch (Exception $e) {
                        $this->_connection->delete(
                            $mediaGalleryTableName, $this->_connection->quoteInto('value_id IN (?)', $newMediaValues)
                        );
                    }
                }
            }

            return $this;
        }

        protected function _saveProducts()
        {
            /** @var $resource Mage_ImportExport_Model_Import_Proxy_Product_Resource */
            $resource = Mage::getModel('importexport/import_proxy_product_resource');
            $priceIsGlobal = Mage::helper('catalog')->isPriceGlobal();
            $strftimeFormat = Varien_Date::convertZendToStrftime(Varien_Date::DATETIME_INTERNAL_FORMAT, true, true);
            $productLimit = null;
            $productsQty = null;

            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                $entityRowsIn = array();
                $entityRowsUp = array();
                $attributes = array();
                $websites = array();
                $categories = array();
                $tierPrices = array();
                $groupPrices = array();
                $mediaGallery = array();
                $uploadedGalleryFiles = array();
                $previousType = null;
                $previousAttributeSet = null;

                // 2013-02-20 Kenneth Roy
                $res_params = array();

                foreach ($bunch as $rowNum => $rowData) {
                    if (!$this->validateRow($rowData, $rowNum)) {
                        continue;
                    }
                    $rowScope = $this->getRowScope($rowData);

                    if (self::SCOPE_DEFAULT == $rowScope) {
                        $rowSku = $rowData[self::COL_SKU];

                        // 1. Entity phase
                        if (isset($this->_oldSku[$rowSku])) { // existing row
                            $entityRowsUp[] = array(
                                'updated_at' => now(),
                                'entity_id' => $this->_oldSku[$rowSku]['entity_id']
                            );
                        } else { // new row
                            if (!$productLimit || $productsQty < $productLimit) {
                                $entityRowsIn[$rowSku] = array(
                                    'entity_type_id' => $this->_entityTypeId,
                                    'attribute_set_id' => $this->_newSku[$rowSku]['attr_set_id'],
                                    'type_id' => $this->_newSku[$rowSku]['type_id'],
                                    'sku' => $rowSku,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                );
                                $productsQty++;
                            } else {
                                $rowSku = null; // sign for child rows to be skipped
                                $this->_rowsToSkip[$rowNum] = true;
                                continue;
                            }
                        }
                    } elseif (null === $rowSku) {
                        $this->_rowsToSkip[$rowNum] = true;
                        continue; // skip rows when SKU is NULL
                    } elseif (self::SCOPE_STORE == $rowScope) { // set necessary data from SCOPE_DEFAULT row
                        $rowData[self::COL_TYPE] = $this->_newSku[$rowSku]['type_id'];
                        $rowData['attribute_set_id'] = $this->_newSku[$rowSku]['attr_set_id'];
                        $rowData[self::COL_ATTR_SET] = $this->_newSku[$rowSku]['attr_set_code'];
                    }
                    if (!empty($rowData['_product_websites'])) { // 2. Product-to-Website phase
                        $websites[$rowSku][$this->_websiteCodeToId[$rowData['_product_websites']]] = true;
                    }

                    // 3. Categories phase
                    $categoryPath = empty($rowData[self::COL_CATEGORY]) ? '' : $rowData[self::COL_CATEGORY];
                    if (!empty($rowData[self::COL_ROOT_CATEGORY])) {
                        $categoryId = $this->_categoriesWithRoots[$rowData[self::COL_ROOT_CATEGORY]][$categoryPath];
                        $categories[$rowSku][$categoryId] = true;
                    } elseif (!empty($categoryPath)) {
                        $categories[$rowSku][$this->_categories[$categoryPath]] = true;
                    }

                    if (!empty($rowData['_tier_price_website'])) { // 4.1. Tier prices phase
                        $tierPrices[$rowSku][] = array(
                            'all_groups' => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                            'customer_group_id' => ($rowData['_tier_price_customer_group'] == self::VALUE_ALL)
                                ? 0 : $rowData['_tier_price_customer_group'],
                            'qty' => $rowData['_tier_price_qty'],
                            'value' => $rowData['_tier_price_price'],
                            'website_id' => (self::VALUE_ALL == $rowData['_tier_price_website'] || $priceIsGlobal)
                                ? 0 : $this->_websiteCodeToId[$rowData['_tier_price_website']]
                        );
                    }
                    if (!empty($rowData['_group_price_website'])) { // 4.2. Group prices phase
                        $groupPrices[$rowSku][] = array(
                            'all_groups' => $rowData['_group_price_customer_group'] == self::VALUE_ALL,
                            'customer_group_id' => ($rowData['_group_price_customer_group'] == self::VALUE_ALL)
                                ? 0 : $rowData['_group_price_customer_group'],
                            'value' => $rowData['_group_price_price'],
                            'website_id' => (self::VALUE_ALL == $rowData['_group_price_website'] || $priceIsGlobal)
                                ? 0 : $this->_websiteCodeToId[$rowData['_group_price_website']]
                        );
                    }
                    foreach ($this->_imagesArrayKeys as $imageCol) {
                        if (!empty($rowData[$imageCol])) { // 5. Media gallery phase
                            if (!array_key_exists($rowData[$imageCol], $uploadedGalleryFiles)) {
                                $uploadedGalleryFiles[$rowData[$imageCol]] = $this->_uploadMediaFiles($rowData[$imageCol]);
                            }
                            $rowData[$imageCol] = $uploadedGalleryFiles[$rowData[$imageCol]];
                            //  $image = $rowData[$imageCol];
                        }
                    }
                    /*foreach ($this->_imagesArrayKeys as $imageCol) {
                        if (empty($rowData[$imageCol]) && isset($image)) {
                            $rowData[$imageCol] = $image;
                        }
                    }*/

                    if (!empty($rowData['_media_image'])) {
                        $mediaGallery[$rowSku][] = array(
                            'attribute_id' => $rowData['_media_attribute_id'],
                            'label' => $rowData['_media_lable'],
                            'position' => $rowData['_media_position'],
                            'disabled' => $rowData['_media_is_disabled'],
                            'value' => $rowData['_media_image']
                        );
                    }
                    // 6. Attributes phase
                    $rowStore = self::SCOPE_STORE == $rowScope ? $this->_storeCodeToId[$rowData[self::COL_STORE]] : 0;
                    $productType = $rowData[self::COL_TYPE];
                    if (!is_null($rowData[self::COL_TYPE])) {
                        $previousType = $rowData[self::COL_TYPE];
                    }
                    if (!is_null($rowData[self::COL_ATTR_SET])) {
                        $previousAttributeSet = $rowData[Mage_ImportExport_Model_Import_Entity_Product::COL_ATTR_SET];
                    }
                    if (self::SCOPE_NULL == $rowScope) {
                        // for multiselect attributes only
                        if (!is_null($previousAttributeSet)) {
                            $rowData[Mage_ImportExport_Model_Import_Entity_Product::COL_ATTR_SET] = $previousAttributeSet;
                        }
                        if (is_null($productType) && !is_null($previousType)) {
                            $productType = $previousType;
                        }
                        if (is_null($productType)) {
                            continue;
                        }
                    }
                    $rowData = $this->_productTypeModels[$productType]->prepareAttributesForSave($rowData);
                    $product = Mage::getModel('importexport/import_proxy_product', $rowData);

                    foreach ($rowData as $attrCode => $attrValue) {

                        $attribute = $resource->getAttribute($attrCode);
                        if ('multiselect' != $attribute->getFrontendInput()
                            && self::SCOPE_NULL == $rowScope
                        ) {
                            continue; // skip attribute processing for SCOPE_NULL rows
                        }
                        $attrId = $attribute->getId();
                        $backModel = $attribute->getBackendModel();
                        $attrTable = $attribute->getBackend()->getTable();
                        $storeIds = array(0);

                        if ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                            $attrValue = gmstrftime($strftimeFormat, strtotime($attrValue));
                        } elseif ($backModel) {
                            $attribute->getBackend()->beforeSave($product);
                            $attrValue = $product->getData($attribute->getAttributeCode());
                        }
                        if (self::SCOPE_STORE == $rowScope) {
                            if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                                // check website defaults already set
                                if (!isset($attributes[$attrTable][$rowSku][$attrId][$rowStore])) {
                                    $storeIds = $this->_storeIdToWebsiteStoreIds[$rowStore];
                                }
                            } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                                $storeIds = array($rowStore);
                            }
                        }
                        foreach ($storeIds as $storeId) {
                            // 2013-02-20 Kenneth Roy
                            if (($attrCode == 'res_prices') || ($attrCode == 'res_excluded_dates') || ($attrCode == 'res_serialnumbers')) {
                                if ($attrValue) {
                                    $res_params[$rowSku][$attrCode][$storeId] = $attrValue;
                                }
                            }
                            if ('multiselect' == $attribute->getFrontendInput()) {

                                // 2013-02-20 Kenneth Roy
                                $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                                /*
                                if(!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                                    $attributes[$attrTable][$rowSku][$attrId][$storeId] = '';
                                } else {
                                    $attributes[$attrTable][$rowSku][$attrId][$storeId] .= ',';
                                }
                                $attributes[$attrTable][$rowSku][$attrId][$storeId] .= $attrValue;
                                */
                            } else {
                                $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                            }
                        }
                        $attribute->setBackendModel($backModel); // restore 'backend_model' to avoid 'default' setting
                    }
                }

                // 2013-02-20 Kenneth Roy
                $this->_saveProductEntity($entityRowsIn, $entityRowsUp)
                    ->_saveProductWebsites($websites)
                    ->_saveProductCategories($categories)
                    ->_saveProductTierPrices($tierPrices)
                    ->_saveProductGroupPrices($groupPrices)
                    ->_saveMediaGallery($mediaGallery)
                    ->_saveProductAttributes($attributes)
                    ->_saveResParams($res_params);
            }
            return $this;
        }

        private function _getPeriodType($value)
        {
            $periodType = '';
            switch ($value) {
                case 'Minute':
                    $periodType = ITwebexperts_Payperrentals_Model_Product_Periodtype::MINUTES;
                    break;
                case 'Hour':
                    $periodType = ITwebexperts_Payperrentals_Model_Product_Periodtype::HOURS;
                    break;
                case 'Day':
                    $periodType = ITwebexperts_Payperrentals_Model_Product_Periodtype::DAYS;
                    break;
                case 'Week':
                    $periodType = ITwebexperts_Payperrentals_Model_Product_Periodtype::WEEKS;
                    break;
                case 'Month':
                    $periodType = ITwebexperts_Payperrentals_Model_Product_Periodtype::MONTHS;
                    break;
                case 'Year':
                    $periodType = ITwebexperts_Payperrentals_Model_Product_Periodtype::YEARS;
                    break;
            }
            return $periodType;
        }

        // 2013-02-20 Kenneth Roy
        protected function _saveResParams(array $res_params)
        {
            foreach ($res_params as $sku => $skuData) {

                $productId = $this->_newSku[$sku]['entity_id'];


                $delEntries = Mage::getModel('payperrentals/serialnumbers')
                    ->getCollection()
                    ->addFieldToFilter('entity_id', $productId)
                    ->load();
                foreach ($delEntries as $delEntry) {
                    $delEntry->delete();
                }
                // 2013-03-12 Kenneth Roy start

                foreach ($skuData as $attrCode => $attrData) {
                    foreach ($attrData as $storeId => $value) {

                        // res_prices
                        if ($attrCode == 'res_prices') {
                            $delEntries = Mage::getModel('payperrentals/reservationprices')
                                ->getCollection()
                                ->addEntityStoreFilter($productId, $storeId)
                                ->load();
                            foreach ($delEntries as $delEntry) {
                                $delEntry->delete();
                            }
                            $arrValues = explode(";", $value);
                            foreach ($arrValues as $arrValue) {
                                $insertValue = explode("=", $arrValue);

                                $periodType = $this->_getPeriodType($insertValue[1]);
                                $periodTypeAdditional = $this->_getPeriodType($insertValue[8]);
                                $resModel = Mage::getModel('payperrentals/reservationprices');
                                $resModel->setEntityId($productId)
                                    ->setStoreId($storeId)
                                    ->setNumberof($insertValue[0])
                                    ->setPtype($periodType)
                                    ->setDateFrom($insertValue[5])
                                    ->setDateTo($insertValue[6])
                                    ->setPrice($insertValue[2])
                                    ->setQtyStart($insertValue[3])
                                    ->setQtyEnd($insertValue[4])
                                    ->setPriceadditional($insertValue[7])
                                    ->setPtypeadditional($periodTypeAdditional)
                                    ->setCustomersGroup($insertValue[9])
                                    ->save();
                            }
                        }

                        // res_excluded_dates
                        if ($attrCode == 'res_excluded_dates') {
                            $delEntries = Mage::getModel('payperrentals/excludeddates')
                                ->getCollection()
                                ->addProductStoreFilter($productId, $storeId)
                                ->load();
                            foreach ($delEntries as $delEntry) {
                                $delEntry->delete();
                            }
                            $arrValues = explode(";", $value);
                            foreach ($arrValues as $arrValue) {
                                $insertValue = explode("=", $arrValue);
                                $_disabledType = '';
                                switch ($insertValue[2]) {
                                    case 'None':
                                        $_disabledType = 'none';
                                        break;
                                    case 'Day of Week':
                                        $_disabledType = 'dayweek';
                                        break;
                                    case 'Daily':
                                        $_disabledType = 'daily';
                                        break;
                                    case 'Monthly':
                                        $_disabledType = 'monthly';
                                        break;
                                    case 'Yearly':
                                        $_disabledType = 'yearly';
                                        break;
                                }

                                $resModel = Mage::getModel('payperrentals/excludeddates');
                                $resModel->setEntityId($productId)
                                    ->setStoreId($storeId)
                                    ->setDisabledFrom($insertValue[0])
                                    ->setDisabledTo($insertValue[1])
                                    ->setDisabledType($_disabledType)
                                    ->save();
                            }
                        }

                        // res_serialnumbers
                        if ($attrCode == 'res_serialnumbers') {
                            $arrValues = explode(";", $value);
                            foreach ($arrValues as $arrValue) {
                                $insertValue = explode("=", $arrValue);
                                $_status = '';
                                switch ($insertValue[1]) {
                                    case 'Available':
                                        $_status = 'A';
                                        break;
                                    case 'Out':
                                        $_status = 'O';
                                        break;
                                    case 'Broken':
                                        $_status = 'B';
                                        break;
                                    case 'Maintenance':
                                        $_status = 'M';
                                        break;
                                }
                                $resModel = Mage::getModel('payperrentals/serialnumbers');
                                $resModel->setEntityId($productId)
                                    ->setSn($insertValue[0])
                                    ->setStatus($_status)
                                    ->save();
                            }
                        }

                    }
                }
            }
        }


        // 2013-02-21 Kenneth Roy
        public function isAttributeValid($attrCode, array $attrParams, array $rowData, $rowNum)
        {
            switch ($attrParams['type']) {
                case 'varchar':
                    $val = Mage::helper('core/string')->cleanString($rowData[$attrCode]);
                    $valid = Mage::helper('core/string')->strlen($val) < self::DB_MAX_VARCHAR_LENGTH;
                    break;
                case 'decimal':
                    $val = trim($rowData[$attrCode]);
                    $valid = (float)$val == $val;
                    break;
                case 'select':
                    $valid = isset($attrParams['options'][strtolower($rowData[$attrCode])]);
                    break;

                // 2013-02-20 Kenneth Roy
                case 'multiselect':
                    $valid = true;
                    break;
                case 'int':
                    $val = trim($rowData[$attrCode]);
                    $valid = (int)$val == $val;
                    break;
                case 'datetime':
                    $val = trim($rowData[$attrCode]);
                    $valid = strtotime($val) !== false
                        || preg_match('/^\d{2}.\d{2}.\d{2,4}(?:\s+\d{1,2}.\d{1,2}(?:.\d{1,2})?)?$/', $val);
                    break;
                case 'text':
                    $val = Mage::helper('core/string')->cleanString($rowData[$attrCode]);
                    $valid = Mage::helper('core/string')->strlen($val) < self::DB_MAX_TEXT_LENGTH;
                    break;
                default:
                    $valid = true;
                    break;
            }

            if (!$valid) {
                $this->addRowError(Mage::helper('importexport')->__("Invalid value for '%s'"), $rowNum, $attrCode);
            } elseif (!empty($attrParams['is_unique'])) {
                if (isset($this->_uniqueAttributes[$attrCode][$rowData[$attrCode]])) {
                    $this->addRowError(Mage::helper('importexport')->__("Duplicate Unique Attribute for '%s'"), $rowNum,
                        $attrCode);
                    return false;
                }
                $this->_uniqueAttributes[$attrCode][$rowData[$attrCode]] = true;
            }
            return (bool)$valid;
        }
    }
} else {
    class ITwebexperts_Itwebcommon_Model_ImportExport_Import_Entity_Product extends Mage_ImportExport_Model_Import_Entity_Product
    {

    }
}