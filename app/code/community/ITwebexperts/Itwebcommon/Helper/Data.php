<?php

/**
 * Class ITwebexperts_Payperrentals_Helper_Data
 */
class ITwebexperts_Itwebcommon_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_hasFooman;
    protected $_hasWarehouse;


    public function hasWarehouse()
    {
        return Mage::helper('core')->isModuleEnabled('ITwebexperts_PPRWarehouse');
    }

    public function hasFooman()
    {
        if (is_null($this->_hasFooman)) {
            $modules = (array)Mage::getConfig()->getNode('modules')->children();
            if (isset($modules['Fooman_PdfCustomiser'])) {
                $this->_hasFooman = true;
            } else {
                $this->_hasFooman = false;
            }
        }
        return $this->_hasFooman;
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