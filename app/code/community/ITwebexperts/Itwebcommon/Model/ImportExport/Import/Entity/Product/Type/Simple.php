<?php
if(Mage::helper('itwebcommon')->hasPayperrentals()){
	class ITwebexperts_Itwebcommon_Model_ImportExport_Import_Entity_Product_Type_Simple
		extends Mage_ImportExport_Model_Import_Entity_Product_Type_Simple
	{
		public function prepareAttributesForSave(array $rowData)
		{
			$resultAttrs = array();

			foreach ($this->_getProductAttributes($rowData) as $attrCode => $attrParams) {
				if (!$attrParams['is_static']) {
					if (isset($rowData[$attrCode]) && strlen($rowData[$attrCode])) {

						// 2013-02-20 Kenneth Roy
						$resultAttrs[$attrCode] =
							('select' == $attrParams['type'])
							? $attrParams['options'][strtolower($rowData[$attrCode])]
							: $rowData[$attrCode];
					} elseif (array_key_exists($attrCode, $rowData)) {
						$resultAttrs[$attrCode] = $rowData[$attrCode];
					} elseif (null !== $attrParams['default_value']) {
						$resultAttrs[$attrCode] = $attrParams['default_value'];
					}
				}
			}
			return $resultAttrs;
		}
	}
}else{
	class ITwebexperts_Itwebcommon_Model_ImportExport_Import_Entity_Product_Type_Simple
		extends Mage_ImportExport_Model_Import_Entity_Product_Type_Simple
	{

	}
}