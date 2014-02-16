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
 * @package     Mage_Paygate
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class ITwebexperts_Itwebcommon_Model_Paygate_Authorizenet extends Mage_Paygate_Model_Authorizenet
{
    public function authorizeOnly(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for authorization.'));
        }

        //$this->_initCardsStorage($payment);

        $exceptionMessage = $this->_placeOnly($payment, $amount, self::REQUEST_TYPE_AUTH_ONLY);
        //$payment->setSkipTransactionCreation(false);
        return $exceptionMessage;
    }

    protected function _placeOnly($payment, $amount, $requestType)
    {
        $payment->setAnetTransType($requestType);
        $payment->setAmount($amount);
        $request= $this->_buildRequest($payment);
        $result = $this->_postRequest($request);

        switch ($requestType) {
            case self::REQUEST_TYPE_AUTH_ONLY:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment authorization error.');
                break;
            case self::REQUEST_TYPE_AUTH_CAPTURE:
                $newTransactionType = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
                $defaultExceptionMessage = Mage::helper('paygate')->__('Payment capturing error.');
                break;
        }

        switch ($result->getResponseCode()) {
            case self::RESPONSE_CODE_APPROVED:
                //$this->getCardsStorage($payment)->flushCards();
                //$card = $this->_registerCard($result, $payment);
                $card = new Varien_Object();
                $card
                    ->setRequestedAmount($result->getRequestedAmount())
                    ->setBalanceOnCard($result->getBalanceOnCard())
                    ->setLastTransId($result->getTransactionId())
                    ->setProcessedAmount($result->getAmount())
                    ->setCcType($payment->getCcType())
                    ->setCcOwner($payment->getCcOwner())
                    ->setCcLast4($payment->getCcLast4())
                    ->setCcExpMonth($payment->getCcExpMonth())
                    ->setCcExpYear($payment->getCcExpYear())
                    ->setCcSsIssue($payment->getCcSsIssue())
                    ->setCcSsStartMonth($payment->getCcSsStartMonth())
                    ->setCcSsStartYear($payment->getCcSsStartYear());
                $exceptionMessage = Mage::helper('paygate')->getTransactionMessage(
                    $payment, $requestType, $result->getTransactionId(), $card, $amount);


                return $exceptionMessage;
            case self::RESPONSE_CODE_HELD:
                if ($result->getResponseReasonCode() == self::RESPONSE_REASON_CODE_PENDING_REVIEW_AUTHORIZED
                    || $result->getResponseReasonCode() == self::RESPONSE_REASON_CODE_PENDING_REVIEW
                ) {
                    //$card = $this->_registerCard($result, $payment);
                    $card = new Varien_Object();
                    $card
                        ->setRequestedAmount($result->getRequestedAmount())
                        ->setBalanceOnCard($result->getBalanceOnCard())
                        ->setLastTransId($result->getTransactionId())
                        ->setProcessedAmount($result->getAmount())
                        ->setCcType($payment->getCcType())
                        ->setCcOwner($payment->getCcOwner())
                        ->setCcLast4($payment->getCcLast4())
                        ->setCcExpMonth($payment->getCcExpMonth())
                        ->setCcExpYear($payment->getCcExpYear())
                        ->setCcSsIssue($payment->getCcSsIssue())
                        ->setCcSsStartMonth($payment->getCcSsStartMonth())
                        ->setCcSsStartYear($payment->getCcSsStartYear());
                    $exceptionMessage = Mage::helper('paygate')->getTransactionMessage(
                        $payment, $requestType, $result->getTransactionId(), $card, $amount
                    );


                    $payment
                        ->setIsTransactionPending(true)
                        ->setIsFraudDetected(true);
                    return $exceptionMessage;
                }

                $exceptionMessage = $defaultExceptionMessage;
            case self::RESPONSE_CODE_DECLINED:
            case self::RESPONSE_CODE_ERROR:
                $exceptionMessage = $this->_wrapGatewayError($result->getResponseReasonText());
            default:
                $exceptionMessage = $defaultExceptionMessage;
        }
        return $exceptionMessage;
    }

    /**
     * Send authorize request to gateway
     *
     * @param  Mage_Payment_Model_Info $payment
     * @param  decimal $amount
     * @return Mage_Paygate_Model_Authorizenet
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if ($amount <= 0) {
            Mage::throwException(Mage::helper('paygate')->__('Invalid amount for authorization.'));
        }

        $this->_initCardsStorage($payment);
        /*todo this is bad aproach should be checked more when partial authorizations*/
        if ($this->isPartialAuthorization($payment) || $payment->getIsDeposit()) {
            $this->_partialAuthorization($payment, $amount, self::REQUEST_TYPE_AUTH_ONLY);
            $payment->setSkipTransactionCreation(true);
            return $this;
        }

        $this->_place($payment, $amount, self::REQUEST_TYPE_AUTH_ONLY);
        $payment->setSkipTransactionCreation(true);
        return $this;
    }

}
