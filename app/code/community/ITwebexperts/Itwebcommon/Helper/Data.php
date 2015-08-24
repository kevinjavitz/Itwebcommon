<?php

/**
 * Class ITwebexperts_Payperrentals_Helper_Data
 */
class ITwebexperts_Itwebcommon_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_hasFooman;
    protected $_hasWarehouse;
    protected $_isVendorInstalled;
    protected $_vendorAdmin;

    public function isVendorInstalled()
    {
        if (is_null($this->_isVendorInstalled)) {
            $this->_isVendorInstalled = Mage::helper('core')->isModuleEnabled('VES_Vendors');
        }
        return $this->_isVendorInstalled;
    }

    /**
     * Checks if warehouse extension is installed
     * @return bool
     */
    public function hasWarehouse()
    {
        return Mage::helper('core')->isModuleEnabled('ITwebexperts_PPRWarehouse');
    }

    /**
     * Checks if Amasty order attribute is installed
     * @return bool
     */
    public function hasAmastyOrderattr()
    {
        return Mage::helper('core')->isModuleEnabled('Amasty_Orderattr');
    }

    /**
     * Checks if Amasty customer attribute is installed
     * @return bool
     */
    public function hasAmastyCustomerattr()
    {
        return Mage::helper('core')->isModuleEnabled('Amasty_Customerattr');
    }

    /**
     * Checks if payperrentals is installed
     * @return bool
     */
    public function hasPayperrentals()
	{
		return Mage::helper('core')->isModuleEnabled('ITwebexperts_Payperrentals');
	}

    public function isRFQ(){
        if(Mage::app()->getRequest()->getParam('isrfq')){
            return true;
        }
        if(Mage::app()->getRequest()->getModuleName() == 'request4quote' && ((Mage::app()->getRequest()->getControllerName() == 'adminhtml_quote_edit') || (Mage::app()->getRequest()->getControllerName() == 'adminhtml_quote_create'))){
            return true;
        }

        return false;
    }


    public function isVendorAdmin(){
        if($this->isVendorInstalled()) {
            if (is_null($this->_vendorAdmin)) {
                $this->_vendorAdmin = Mage::getSingleton('vendors/session');
            }
            if ($this->_vendorAdmin->getId()) {
                return true;
            }
        }
        return false;
    }

    public function getPayperrentalsPath()
    {
        return BP . DS . 'app' . DS . 'code' . DS . 'community'. DS . 'ITwebexperts' . DS . 'Payperrentals';
    }

    /**
     * Gving an attribute code returns the value for the product
     * @param      $id
     * @param      $attributeCode
     * @param null $storeID
     *
     * @return array|bool|string
     */

    public function getAttributeCodeForId($id, $attributeCode, $storeID = null)
    {
        if (is_null($storeID)) {
            if (Mage::app()->getStore()->isAdmin()) {
                $storeID = Mage::getSingleton('adminhtml/session_quote')->getStoreId();
            } else {
                $storeID = Mage::app()->getStore()->getId();
            }
        }
        return Mage::getResourceModel('catalog/product')->getAttributeRawValue($id, $attributeCode, $storeID);
    }

    public function fileExists($fullpath){
        if(file_exists($fullpath)){
            return true;
        } else {
            return false;
        }
    }
}