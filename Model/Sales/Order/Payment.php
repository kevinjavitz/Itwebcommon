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
        protected function _isCaptureFinal($amountToCapture)
        {
            $amountToCapture = $this->_formatAmount($amountToCapture, true);
            $orderGrandTotal = $this->_formatAmount($this->getOrder()->getBaseGrandTotal(), true);
            if ($this->getOrder()->getDepositpprAmount()) {
                $orderGrandTotal += $this->_formatAmount($this->getOrder()->getDepositpprAmount(), true);
            }
            if ($orderGrandTotal == $this->_formatAmount($this->getBaseAmountPaid(), true) + $amountToCapture) {
                if (false !== $this->getShouldCloseParentTransaction()) {
                    $this->setShouldCloseParentTransaction(true);
                }
                return true;
            }
            return false;
        }
	}
}else{
	class ITwebexperts_Itwebcommon_Model_Sales_Order_Payment extends Mage_Sales_Model_Order_Payment
	{

	}
}