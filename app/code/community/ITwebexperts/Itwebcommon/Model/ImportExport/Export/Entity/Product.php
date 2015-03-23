<?php
if(Mage::helper('itwebcommon')->hasPayperrentals()){
	class ITwebexperts_Itwebcommon_Model_ImportExport_Export_Entity_Product extends Mage_ImportExport_Model_Export_Entity_Product
	{
		private function _getPType($pType){
			$periodType = '';
			switch($pType){
				case ITwebexperts_Payperrentals_Model_Product_Periodtype::MINUTES:
					$periodType = 'Minute';
					break;
				case ITwebexperts_Payperrentals_Model_Product_Periodtype::HOURS:
					$periodType = 'Hour';
					break;
				case ITwebexperts_Payperrentals_Model_Product_Periodtype::DAYS:
					$periodType = 'Day';
					break;
				case ITwebexperts_Payperrentals_Model_Product_Periodtype::WEEKS:
					$periodType = 'Week';
					break;
				case ITwebexperts_Payperrentals_Model_Product_Periodtype::MONTHS:
					$periodType = 'Month';
					break;
				case ITwebexperts_Payperrentals_Model_Product_Periodtype::YEARS:
					$periodType = 'Year';
					break;
			}
			return $periodType;
		}

		public function export()
		{
			//Execution time may be very long
			set_time_limit(0);

			/** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
			$validAttrCodes  = $this->_getExportAttrCodes();
			$writer          = $this->getWriter();
			$defaultStoreId  = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;

			$memoryLimit = trim(ini_get('memory_limit'));
			$lastMemoryLimitLetter = strtolower($memoryLimit[strlen($memoryLimit)-1]);
			switch($lastMemoryLimitLetter) {
				case 'g':
					$memoryLimit *= 1024;
				case 'm':
					$memoryLimit *= 1024;
				case 'k':
					$memoryLimit *= 1024;
					break;
				default:
					// minimum memory required by Magento
					$memoryLimit = 250000000;
			}

			// Tested one product to have up to such size
			$memoryPerProduct = 100000;
			// Decrease memory limit to have supply
			$memoryUsagePercent = 0.8;
			// Minimum Products limit
			$minProductsLimit = 500;

			$limitProducts = intval(($memoryLimit  * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct);
			if ($limitProducts < $minProductsLimit) {
				$limitProducts = $minProductsLimit;
			}
			$offsetProducts = 0;

			while (true) {
				++$offsetProducts;

				$dataRows        = array();
				$rowCategories   = array();
				$rowWebsites     = array();
				$rowTierPrices   = array();
				$rowGroupPrices  = array();
				$rowMultiselects = array();
				$mediaGalery     = array();

				// prepare multi-store values and system columns values
				foreach ($this->_storeIdToCode as $storeId => &$storeCode) { // go through all stores
					$collection = $this->_prepareEntityCollection(Mage::getResourceModel('catalog/product_collection'));
					$collection
						->setStoreId($storeId)
						->setPage($offsetProducts, $limitProducts);
					if ($collection->getCurPage() < $offsetProducts) {
						break;
					}
					$collection->load();

					if ($collection->count() == 0) {
						break;
					}

					if ($defaultStoreId == $storeId) {
						$collection->addCategoryIds()->addWebsiteNamesToResult();

						// tier and group price data getting only once
						$rowTierPrices = $this->_prepareTierPrices($collection->getAllIds());
						$rowGroupPrices = $this->_prepareGroupPrices($collection->getAllIds());

						// getting media gallery data
						$mediaGalery = $this->_prepareMediaGallery($collection->getAllIds());
					}
					foreach ($collection as $itemId => $item) { // go through all products
						$rowIsEmpty = true; // row is empty by default

						foreach ($validAttrCodes as &$attrCode) { // go through all valid attribute codes
							$attrValue = $item->getData($attrCode);

							// 2013-02-20 Kenneth Roy start
							// res_prices
							if ($attrCode == 'res_prices') {
								$productid = Mage::getModel('catalog/product')->getIdBySku($item->getData('sku'));
								$collectionPrices = Mage::getModel('payperrentals/reservationprices')
										->getCollection()
										->addEntityStoreFilter($productid, $storeId);

								$resprices = array();
								foreach ($collectionPrices as $itemcol) {
									$periodType = $this->_getPType($itemcol->getPtype());
									$periodTypeAdditional = $this->_getPType($itemcol->getPtypeadditional());
									$resprices[] = $itemcol->getNumberof().'='.$periodType.'='.$itemcol->getPrice().'='.$itemcol->getQtyStart().'='.$itemcol->getQtyEnd().'='.$itemcol->getDateFrom().'='.$itemcol->getDateTo().'='.$itemcol->getPriceadditional().'='.$periodTypeAdditional.'='.$itemcol->getCustomersGroup();
								}
								if(count($resprices) > 0){
									$attrValue = implode(';',$resprices);
								}
							}

							if ($attrCode == 'inventory_serialized') {
								$attrValue = '';
							}

							// res_excluded_dates
							if ($attrCode == 'res_excluded_dates') {
								$productid = Mage::getModel('catalog/product')->getIdBySku($item->getData('sku'));
								$collectionExcludedDates = Mage::getModel('payperrentals/excludeddates')
										->getCollection()
										->addProductStoreFilter($productid, $storeId);

								$resExcludedDates = array();
								foreach ($collectionExcludedDates as $itemcol) {
									$_disabledType = '';
									switch($itemcol->getDisabledType()) {
										case 'none':
											$_disabledType = 'None';
											break;
										case 'dayweek':
											$_disabledType = 'Day of Week';
											break;
										case 'daily':
											$_disabledType = 'Daily';
											break;
										case 'monthly':
											$_disabledType = 'Monthly';
											break;
										case 'yearly':
											$_disabledType = 'Yearly';
											break;
									}
									$resExcludedDates[] = $itemcol->getDisabledFrom().'='.$itemcol->getDisabledTo().'='.$_disabledType;
								}
								if(count($resExcludedDates) > 0){
									$attrValue = implode(';',$resExcludedDates);
								}
							}

							// res_serialnumbers
							if ($attrCode == 'res_serialnumbers') {
								$productid = Mage::getModel('catalog/product')->getIdBySku($item->getData('sku'));
								$collectionSerials = Mage::getModel('payperrentals/serialnumbers')
										->getCollection()
										->addEntityIdFilter($productid);
								$resSerialnumbers = array();
								foreach ($collectionSerials as $itemcol) {
									$_status = '';
									switch($itemcol->getStatus()) {
										case 'A':
											$_status = 'Available';
											break;
										case 'O':
											$_status = 'Out';
											break;
										case 'B':
											$_status = 'Broken';
											break;
										case 'M':
											$_status = 'Maintenance';
											break;
									}
									$resSerialnumbers[] = $itemcol->getSn().'='.$_status;
								}
								if(count($resSerialnumbers) > 0){
									$attrValue = implode(';',$resSerialnumbers);
								}
							}
							// 2013-02-20 Kenneth Roy end

							if (!empty($this->_attributeValues[$attrCode])) {
								if ($this->_attributeTypes[$attrCode] == 'multiselect') {
									// 2013-02-20 Kenneth Roy
									/*
									$attrValue = explode(',', $attrValue);
									$attrValue = array_intersect_key(
										$this->_attributeValues[$attrCode],
										array_flip($attrValue)
									);
									$rowMultiselects[$itemId][$attrCode] = $attrValue;
									*/
									$attrValue = explode(',', $attrValue);
									$attrValue = array_intersect_key(
										$this->_attributeValues[$attrCode],
										array_flip($attrValue)
									);
									$attrValue = implode(',', $attrValue);
									//$rowMultiselects[$itemId][$attrCode] = $attrValue;
								} else if (isset($this->_attributeValues[$attrCode][$attrValue])) {
									$attrValue = $this->_attributeValues[$attrCode][$attrValue];
								} else {
									$attrValue = null;
								}
							}
							// do not save value same as default or not existent
							if ($storeId != $defaultStoreId
								&& isset($dataRows[$itemId][$defaultStoreId][$attrCode])
								&& $dataRows[$itemId][$defaultStoreId][$attrCode] == $attrValue
							) {
								$attrValue = null;
							}
							if (is_scalar($attrValue)) {
								$dataRows[$itemId][$storeId][$attrCode] = $attrValue;
								$rowIsEmpty = false; // mark row as not empty
							}
						}
						if ($rowIsEmpty) { // remove empty rows
							unset($dataRows[$itemId][$storeId]);
						} else {
							$attrSetId = $item->getAttributeSetId();
							$dataRows[$itemId][$storeId][self::COL_STORE]    = $storeCode;
							$dataRows[$itemId][$storeId][self::COL_ATTR_SET] = $this->_attrSetIdToName[$attrSetId];
							$dataRows[$itemId][$storeId][self::COL_TYPE]     = $item->getTypeId();

							if ($defaultStoreId == $storeId) {
								$rowWebsites[$itemId]   = $item->getWebsites();
								$rowCategories[$itemId] = $item->getCategoryIds();
							}
						}
						$item = null;
					}
					$collection->clear();
				}

				if ($collection->getCurPage() < $offsetProducts) {
					break;
				}

				// remove unused categories
				$allCategoriesIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
				foreach ($rowCategories as &$categories) {
					$categories = array_intersect($categories, $allCategoriesIds);
				}

				// prepare catalog inventory information
				$productIds = array_keys($dataRows);
				$stockItemRows = $this->_prepareCatalogInventory($productIds);

				// prepare links information
				$linksRows = $this->_prepareLinks($productIds);
				$linkIdColPrefix = array(
					Mage_Catalog_Model_Product_Link::LINK_TYPE_RELATED   => '_links_related_',
					Mage_Catalog_Model_Product_Link::LINK_TYPE_UPSELL    => '_links_upsell_',
					Mage_Catalog_Model_Product_Link::LINK_TYPE_CROSSSELL => '_links_crosssell_',
					Mage_Catalog_Model_Product_Link::LINK_TYPE_GROUPED   => '_associated_'
				);
				$configurableProductsCollection = Mage::getResourceModel('catalog/product_collection');
				$configurableProductsCollection->addAttributeToFilter(
					'entity_id',
					array(
						'in'    => $productIds
					)
				)->addAttributeToFilter(
					'type_id',
					array(
						'eq'    => Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE
					)
				);
				$configurableData = array();
				while ($product = $configurableProductsCollection->fetchItem()) {
					$productAttributesOptions = $product->getTypeInstance(true)->getConfigurableOptions($product);

					foreach ($productAttributesOptions as $productAttributeOption) {
						$configurableData[$product->getId()] = array();
						foreach ($productAttributeOption as $optionValues) {
							$configurableData[$product->getId()][] = array(
								'_super_products_sku'           => $optionValues['sku'],
								'_super_attribute_code'         => $optionValues['attribute_code'],
								'_super_attribute_option'       => $optionValues['option_title'],
								'_super_attribute_price_corr'   => $optionValues['pricing_value']
							);
						}
					}
				}

				// prepare custom options information
				$customOptionsData    = array();
				$customOptionsDataPre = array();
				$customOptCols        = array(
					'_custom_option_store', '_custom_option_type', '_custom_option_title', '_custom_option_is_required',
					'_custom_option_price', '_custom_option_sku', '_custom_option_max_characters',
					'_custom_option_sort_order', '_custom_option_row_title', '_custom_option_row_price',
					'_custom_option_row_sku', '_custom_option_row_sort'
				);

				foreach ($this->_storeIdToCode as $storeId => &$storeCode) {
					$options = Mage::getResourceModel('catalog/product_option_collection')
						->reset()
						->addTitleToResult($storeId)
						->addPriceToResult($storeId)
						->addProductToFilter($productIds)
						->addValuesToResult($storeId);

					foreach ($options as $option) {
						$row = array();
						$productId = $option['product_id'];
						$optionId  = $option['option_id'];
						$customOptions = isset($customOptionsDataPre[$productId][$optionId])
									   ? $customOptionsDataPre[$productId][$optionId]
									   : array();

						if ($defaultStoreId == $storeId) {
							$row['_custom_option_type']           = $option['type'];
							$row['_custom_option_title']          = $option['title'];
							$row['_custom_option_is_required']    = $option['is_require'];
							$row['_custom_option_price'] = $option['price']
								. ($option['price_type'] == 'percent' ? '%' : '');
							$row['_custom_option_sku']            = $option['sku'];
							$row['_custom_option_max_characters'] = $option['max_characters'];
							$row['_custom_option_sort_order']     = $option['sort_order'];

							// remember default title for later comparisons
							$defaultTitles[$option['option_id']] = $option['title'];
						} elseif ($option['title'] != $customOptions[0]['_custom_option_title']) {
							$row['_custom_option_title'] = $option['title'];
						}
						$values = $option->getValues();
						if ($values) {
							$firstValue = array_shift($values);
							$priceType  = $firstValue['price_type'] == 'percent' ? '%' : '';

							if ($defaultStoreId == $storeId) {
								$row['_custom_option_row_title'] = $firstValue['title'];
								$row['_custom_option_row_price'] = $firstValue['price'] . $priceType;
								$row['_custom_option_row_sku']   = $firstValue['sku'];
								$row['_custom_option_row_sort']  = $firstValue['sort_order'];

								$defaultValueTitles[$firstValue['option_type_id']] = $firstValue['title'];
							} elseif ($firstValue['title'] != $customOptions[0]['_custom_option_row_title']) {
								$row['_custom_option_row_title'] = $firstValue['title'];
							}
						}
						if ($row) {
							if ($defaultStoreId != $storeId) {
								$row['_custom_option_store'] = $this->_storeIdToCode[$storeId];
							}
							$customOptionsDataPre[$productId][$optionId][] = $row;
						}
						foreach ($values as $value) {
							$row = array();
							$valuePriceType = $value['price_type'] == 'percent' ? '%' : '';

							if ($defaultStoreId == $storeId) {
								$row['_custom_option_row_title'] = $value['title'];
								$row['_custom_option_row_price'] = $value['price'] . $valuePriceType;
								$row['_custom_option_row_sku']   = $value['sku'];
								$row['_custom_option_row_sort']  = $value['sort_order'];
							} elseif ($value['title'] != $customOptions[0]['_custom_option_row_title']) {
								$row['_custom_option_row_title'] = $value['title'];
							}
							if ($row) {
								if ($defaultStoreId != $storeId) {
									$row['_custom_option_store'] = $this->_storeIdToCode[$storeId];
								}
								$customOptionsDataPre[$option['product_id']][$option['option_id']][] = $row;
							}
						}
						$option = null;
					}
					$options = null;
				}
				foreach ($customOptionsDataPre as $productId => &$optionsData) {
					$customOptionsData[$productId] = array();

					foreach ($optionsData as $optionId => &$optionRows) {
						$customOptionsData[$productId] = array_merge($customOptionsData[$productId], $optionRows);
					}
					unset($optionRows, $optionsData);
				}
				unset($customOptionsDataPre);

				if ($offsetProducts == 1) {
					// create export file
					$headerCols = array_merge(
						array(
							self::COL_SKU, self::COL_STORE, self::COL_ATTR_SET,
							self::COL_TYPE, self::COL_CATEGORY, self::COL_ROOT_CATEGORY, '_product_websites'
						),
						$validAttrCodes,
						reset($stockItemRows) ? array_keys(end($stockItemRows)) : array(),
						array(),
						array(
							'_links_related_sku', '_links_related_position', '_links_crosssell_sku',
							'_links_crosssell_position', '_links_upsell_sku', '_links_upsell_position',
							'_associated_sku', '_associated_default_qty', '_associated_position'
						),
						array('_tier_price_website', '_tier_price_customer_group', '_tier_price_qty', '_tier_price_price'),
						array('_group_price_website', '_group_price_customer_group', '_group_price_price'),
						array(
							'_media_attribute_id',
							'_media_image',
							'_media_lable',
							'_media_position',
							'_media_is_disabled'
						)
					);

					// have we merge custom options columns
					if ($customOptionsData) {
						$headerCols = array_merge($headerCols, $customOptCols);
					}

					// have we merge configurable products data
					if ($configurableData) {
						$headerCols = array_merge($headerCols, array(
							'_super_products_sku', '_super_attribute_code',
							'_super_attribute_option', '_super_attribute_price_corr'
						));
					}

					$writer->setHeaderCols($headerCols);
				}

				foreach ($dataRows as $productId => &$productData) {
					foreach ($productData as $storeId => &$dataRow) {
						if ($defaultStoreId != $storeId) {
							$dataRow[self::COL_SKU]      = null;
							$dataRow[self::COL_ATTR_SET] = null;
							$dataRow[self::COL_TYPE]     = null;
						} else {
							$dataRow[self::COL_STORE] = null;
							$dataRow = array_merge($dataRow, $stockItemRows[$productId]);
						}
						if (count($rowCategories[$productId])) {
							$this->_updateDataWithCategoryColumns($dataRow, $rowCategories, $productId);
						}

						if ($rowWebsites[$productId]) {
							$dataRow['_product_websites'] = $this->_websiteIdToCode[array_shift($rowWebsites[$productId])];
						}
						if (!empty($rowTierPrices[$productId])) {
							$dataRow = array_merge($dataRow, array_shift($rowTierPrices[$productId]));
						}
						if (!empty($rowGroupPrices[$productId])) {
							$dataRow = array_merge($dataRow, array_shift($rowGroupPrices[$productId]));
						}
						if (!empty($mediaGalery[$productId])) {
							$dataRow = array_merge($dataRow, array_shift($mediaGalery[$productId]));
						}
						foreach ($linkIdColPrefix as $linkId => &$colPrefix) {
							if (!empty($linksRows[$productId][$linkId])) {
								$linkData = array_shift($linksRows[$productId][$linkId]);
								$dataRow[$colPrefix . 'position'] = $linkData['position'];
								$dataRow[$colPrefix . 'sku'] = $linkData['sku'];

								if (null !== $linkData['default_qty']) {
									$dataRow[$colPrefix . 'default_qty'] = $linkData['default_qty'];
								}
							}
						}
						if (!empty($customOptionsData[$productId])) {
							$dataRow = array_merge($dataRow, array_shift($customOptionsData[$productId]));
						}
						if (!empty($configurableData[$productId])) {
							$dataRow = array_merge($dataRow, array_shift($configurableData[$productId]));
						}
						if(!empty($rowMultiselects[$productId])) {
							foreach ($rowMultiselects[$productId] as $attrKey => $attrVal) {
								if (!empty($rowMultiselects[$productId][$attrKey])) {
									$dataRow[$attrKey] = array_shift($rowMultiselects[$productId][$attrKey]);
								}
							}
						}

						$writer->writeRow($dataRow);
					}
					// calculate largest links block
					$largestLinks = 0;

					if (isset($linksRows[$productId])) {
						$linksRowsKeys = array_keys($linksRows[$productId]);
						foreach ($linksRowsKeys as $linksRowsKey) {
							$largestLinks = max($largestLinks, count($linksRows[$productId][$linksRowsKey]));
						}
					}
					$additionalRowsCount = max(
						count($rowCategories[$productId]),
						count($rowWebsites[$productId]),
						$largestLinks
					);
					if (!empty($rowTierPrices[$productId])) {
						$additionalRowsCount = max($additionalRowsCount, count($rowTierPrices[$productId]));
					}
					if (!empty($rowGroupPrices[$productId])) {
						$additionalRowsCount = max($additionalRowsCount, count($rowGroupPrices[$productId]));
					}
					if (!empty($mediaGalery[$productId])) {
						$additionalRowsCount = max($additionalRowsCount, count($mediaGalery[$productId]));
					}
					if (!empty($customOptionsData[$productId])) {
						$additionalRowsCount = max($additionalRowsCount, count($customOptionsData[$productId]));
					}
					if (!empty($configurableData[$productId])) {
						$additionalRowsCount = max($additionalRowsCount, count($configurableData[$productId]));
					}
					if (!empty($rowMultiselects[$productId])) {
						foreach($rowMultiselects[$productId] as $attributes) {
							$additionalRowsCount = max($additionalRowsCount, count($attributes));
						}
					}

					if ($additionalRowsCount) {
						for ($i = 0; $i < $additionalRowsCount; $i++) {
							$dataRow = array();

							$this->_updateDataWithCategoryColumns($dataRow, $rowCategories, $productId);
							if ($rowWebsites[$productId]) {
								$dataRow['_product_websites'] = $this
									->_websiteIdToCode[array_shift($rowWebsites[$productId])];
							}
							if (!empty($rowTierPrices[$productId])) {
								$dataRow = array_merge($dataRow, array_shift($rowTierPrices[$productId]));
							}
							if (!empty($rowGroupPrices[$productId])) {
								$dataRow = array_merge($dataRow, array_shift($rowGroupPrices[$productId]));
							}
							if (!empty($mediaGalery[$productId])) {
								$dataRow = array_merge($dataRow, array_shift($mediaGalery[$productId]));
							}
							foreach ($linkIdColPrefix as $linkId => &$colPrefix) {
								if (!empty($linksRows[$productId][$linkId])) {
									$linkData = array_shift($linksRows[$productId][$linkId]);
									$dataRow[$colPrefix . 'position'] = $linkData['position'];
									$dataRow[$colPrefix . 'sku'] = $linkData['sku'];

									if (null !== $linkData['default_qty']) {
										$dataRow[$colPrefix . 'default_qty'] = $linkData['default_qty'];
									}
								}
							}
							if (!empty($customOptionsData[$productId])) {
								$dataRow = array_merge($dataRow, array_shift($customOptionsData[$productId]));
							}
							if (!empty($configurableData[$productId])) {
								$dataRow = array_merge($dataRow, array_shift($configurableData[$productId]));
							}
							if(!empty($rowMultiselects[$productId])) {
								foreach($rowMultiselects[$productId] as $attrKey=>$attrVal) {
									if(!empty($rowMultiselects[$productId][$attrKey])) {
										$dataRow[$attrKey] = array_shift($rowMultiselects[$productId][$attrKey]);
									}
								}
							}
							$writer->writeRow($dataRow);
						}
					}
				}
			}
			return $writer->getContents();
		}
	}
}else{
	class ITwebexperts_Itwebcommon_Model_ImportExport_Export_Entity_Product extends Mage_ImportExport_Model_Export_Entity_Product
	{

	}
}