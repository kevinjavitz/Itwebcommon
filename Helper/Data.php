<?php

/**
 * Class ITwebexperts_Payperrentals_Helper_Data
 */
class ITwebexperts_Itwebcommon_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_hasFooman;
    protected $_hasWarehouse;

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

}