<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wrapper that performs Paypal Express and Checkout communication
 * Use current Paypal Express method instance
 */
if(Mage::helper('itwebcommon')->hasPayperrentals()){
	class ITwebexperts_Itwebcommon_Model_Paypal_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
	{
		/**
		 * Make sure addresses will be saved without validation errors
		 */
		private function _ignoreAddressValidation()
		{
			$this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
			if (!$this->_quote->getIsVirtual()) {
				$this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
				if (!$this->_config->requireBillingAddress && !$this->_quote->getBillingAddress()->getEmail()) {
					$this->_quote->getBillingAddress()->setSameAsBilling(1);
				}
			}
		}
		/**
		 * Place the order and recurring payment profiles when customer returned from paypal
		 * Until this moment all quote data must be valid
		 *
		 * @param string $token
		 * @param string $shippingMethodCode
		 */
		public function place($token, $shippingMethodCode = null)
		{
			if ($shippingMethodCode) {
				$this->updateShippingMethod($shippingMethodCode);
			}

			$isNewCustomer = false;
			switch ($this->getCheckoutMethod()) {
				case Mage_Checkout_Model_Type_Onepage::METHOD_GUEST:
					$this->_prepareGuestQuote();
					break;
				case Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER:
					$this->_prepareNewCustomerQuote();
					$isNewCustomer = true;
					break;
				default:
					$this->_prepareCustomerQuote();
					break;
			}

			$this->_ignoreAddressValidation();
			$this->_quote->collectTotals();
			$service = Mage::getModel('sales/service_quote', $this->_quote);
			$service->submitAll();
			$this->_quote->save();

			if ($isNewCustomer) {
				try {
					$this->_involveNewCustomer();
				} catch (Exception $e) {
					Mage::logException($e);
				}
			}

			$this->_recurringPaymentProfiles = $service->getRecurringPaymentProfiles();
			// TODO: send recurring profile emails

			$order = $service->getOrder();

			//for some reason after the observer ... $order becomes an blank object... so needs an extra check.
			if (!$order || !is_object($order->getPayment())) {
				return;
			}
			$this->_billingAgreement = $order->getPayment()->getBillingAgreement();

			// commence redirecting to finish payment, if paypal requires it
			if ($order->getPayment()->getAdditionalInformation(
				Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_REDIRECT
			)) {
				$this->_redirectUrl = $this->_config->getExpressCheckoutCompleteUrl($token);
			}

			switch ($order->getState()) {
				// even after placement paypal can disallow to authorize/capture, but will wait until bank transfers money
				case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
					// TODO
					break;
				// regular placement, when everything is ok
				case Mage_Sales_Model_Order::STATE_PROCESSING:
				case Mage_Sales_Model_Order::STATE_COMPLETE:
				case Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW:
					$order->sendNewOrderEmail();
					break;
			}
			$this->_order = $order;
		}

	}
}else{
	class ITwebexperts_Itwebcommon_Model_Paypal_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
	{

	}
}