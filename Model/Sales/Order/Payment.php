<?php
if(Mage::helper('itwebcommon')->hasPayperrentals()){
	class ITwebexperts_Itwebcommon_Model_Sales_Order_Payment extends Mage_Sales_Model_Order_Payment
	{
		/**
		 * Before object save manipulations
		 *
		 * @return Mage_Sales_Model_Order_Payment
		 */
		protected function _beforeSave()
		{
			parent::_beforeSave();

			if($this->getOrder()->getInvoiceId() && $this->isObjectNew()){
				$this->setInvoiceId($this->getOrder()->getInvoiceId());
			}

			return $this;
		}
	}
}else{
	class ITwebexperts_Itwebcommon_Model_Sales_Order_Payment extends Mage_Sales_Model_Order_Payment
	{

	}
}