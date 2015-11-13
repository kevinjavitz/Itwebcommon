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
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
if(class_exists('IWD_Opc_Model_Paypal_Express')){
    class ITwebexperts_Itwebcommon_Model_Paypal_Express_Component extends IWD_Opc_Model_Paypal_Express{

    }
}else{
    class ITwebexperts_Itwebcommon_Model_Paypal_Express_Component extends Mage_Paypal_Model_Express{

    }
}
if(Mage::helper('itwebcommon')->hasPayperrentals()) {
    class ITwebexperts_Itwebcommon_Model_Paypal_Express extends ITwebexperts_Itwebcommon_Model_Paypal_Express_Component
    {

        /**
         * Place an order with authorization or capture action
         *
         * @param Mage_Sales_Model_Order_Payment $payment
         * @param float $amount
         * @return Mage_Paypal_Model_Express
         */
        protected function _placeOrder(Mage_Sales_Model_Order_Payment $payment, $amount)
        {
            $order = $payment->getOrder();
            if ($order->getDepositpprAmount() && !Mage::helper('payperrentals/config')->isChargedDeposit()) {
                $amount += $order->getDepositpprAmount();
            }
            // prepare api call
            $token = $payment->getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_TOKEN);
            $api = $this->_pro->getApi()
                ->setToken($token)
                ->setPayerId($payment->
                    getAdditionalInformation(Mage_Paypal_Model_Express_Checkout::PAYMENT_INFO_TRANSPORT_PAYER_ID))
                ->setAmount($amount)
                ->setPaymentAction($this->_pro->getConfig()->paymentAction)
                ->setNotifyUrl(Mage::getUrl('paypal/ipn/'))
                ->setInvNum($order->getIncrementId())
                ->setCurrencyCode($order->getBaseCurrencyCode())
                ->setPaypalCart(Mage::getModel('paypal/cart', array($order)))
                ->setIsLineItemsEnabled($this->_pro->getConfig()->lineItemsEnabled)
            ;
            if ($order->getIsVirtual()) {
                $api->setAddress($order->getBillingAddress())->setSuppressShipping(true);
            } else {
                $api->setAddress($order->getShippingAddress());
                $api->setBillingAddress($order->getBillingAddress());
            }

            // call api and get details from it
            $api->callDoExpressCheckoutPayment();

            $this->_importToPayment($api, $payment);
            return $this;
        }

    }
}else{
    class ITwebexperts_Itwebcommon_Model_Paypal_Express extends ITwebexperts_Itwebcommon_Model_Paypal_Express_Component
    {
    }
}