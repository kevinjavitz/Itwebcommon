<?php
if(Mage::helper('itwebcommon')->hasWarehouse()){
    class ITwebexperts_Itwebcommon_Model_Sales_Order_Component extends Innoexts_Warehouse_Model_Sales_Order{
    }
}else if(Mage::helper('itwebcommon')->hasAmastyOrderattr()){
    class ITwebexperts_Itwebcommon_Model_Sales_Order_Component extends Amasty_Orderattr_Model_Sales_Order
    {
    }
}else{
    class ITwebexperts_Itwebcommon_Model_Sales_Order_Component extends Mage_Sales_Model_Order
    {
    }
}
if(Mage::helper('itwebcommon')->hasPayperrentals()){
	class ITwebexperts_Itwebcommon_Model_Sales_Order extends ITwebexperts_Itwebcommon_Model_Sales_Order_Component
	{
		/**
		 * Order state protected setter.
		 * By default allows to set any state. Can also update status to default or specified value
		 * Сomplete and closed states are encapsulated intentionally, see the _checkState()
		 *
		 * @param string $state
		 * @param string|bool $status
		 * @param string $comment
		 * @param bool $isCustomerNotified
		 * @param $shouldProtectState
		 * @return Mage_Sales_Model_Order
		 */
		protected function _setState($state, $status = false, $comment = '',
									 $isCustomerNotified = null, $shouldProtectState = false)
		{
			// attempt to set the specified state
			if ($shouldProtectState) {
				if ($this->isStateProtected($state)) {
					Mage::throwException(
						Mage::helper('sales')->__('The Order State "%s" must not be set manually.', $state)
					);
				}
			}
			$this->setData('state', $state);

			// add status history
			if ($status) {
				if ($status === true) {
					$status = $this->getConfig()->getStateDefaultStatus($state);
				}
				$this->setStatus($status);
				$history = $this->addStatusHistoryComment($comment, false); // no sense to set $status again
				$history->setIsCustomerNotified($isCustomerNotified); // for backwards compatibility
			}
			Mage::dispatchEvent('sales_order_status_after', array('order' => $this, 'state' => $state, 'status' => $status, 'comment' => $comment, 'isCustomerNotified' => $isCustomerNotified, 'shouldProtectState' => $shouldProtectState));
			return $this;
		}

		public function getPayment()
		{
			if(Mage::app()->getRequest()->getParam('invoice_id')){
				$invoice_id = Mage::app()->getRequest()->getParam('invoice_id');

				//$invoice = Mage::getModel('sales/order_invoice')->load($invoice_id);
			}


			$isOut = false;
			$paymentMin = null;
			foreach ($this->getPaymentsCollection() as $paymentTemp) {
				if(!$paymentTemp->getInvoiceId()){
					$paymentMin = $paymentTemp;
				}
				if (!$paymentTemp->isDeleted()) {
					$payment = $paymentTemp;

					if(isset($invoice_id) && $paymentTemp->getInvoiceId() == $invoice_id){
						$isOut = true;
						break;
					}
				}
			}

			if(!$isOut && is_object($paymentMin)){
				return $paymentMin;
			}

			if(/*$isOut && */isset($payment) && is_object($payment)){
				return $payment;
			}
			return false;
		}
	}
}else{
	class ITwebexperts_Itwebcommon_Model_Sales_Order extends ITwebexperts_Itwebcommon_Model_Sales_Order_Component
	{

	}
}
