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

if(Mage::helper('itwebcommon')->hasPayperrentals()) {
    class ITwebexperts_Itwebcommon_Model_Paypal_Cart extends Mage_Paypal_Model_Cart
    {
        /**
         * Check the line items and totals according to PayPal business logic limitations
         */
        protected function _validate()
        {
            $this->_areItemsValid = false;
            $this->_areTotalsValid = false;

            $referenceAmount = $this->_salesEntity->getBaseGrandTotal();

            if ($this->_salesEntity->getDepositpprAmount()) {
                $referenceAmount += $this->_salesEntity->getDepositpprAmount();
            }

            $itemsSubtotal = 0;
            foreach ($this->_items as $i) {
                $itemsSubtotal = $itemsSubtotal + $i['qty'] * $i['amount'];
            }
            $sum = $itemsSubtotal + $this->_totals[self::TOTAL_TAX];
            if (!$this->_isShippingAsItem) {
                $sum += $this->_totals[self::TOTAL_SHIPPING];
            }
            if (!$this->_isDiscountAsItem) {
                $sum -= $this->_totals[self::TOTAL_DISCOUNT];
            }
            /**
             * numbers are intentionally converted to strings because of possible comparison error
             * see http://php.net/float
             */
            // match sum of all the items and totals to the reference amount
            if (sprintf('%.4F', $sum) == sprintf('%.4F', $referenceAmount)) {
                $this->_areItemsValid = true;
            }

            // PayPal requires to have discount less than items subtotal
            if (!$this->_isDiscountAsItem) {
                $this->_areTotalsValid = round($this->_totals[self::TOTAL_DISCOUNT], 4) < round($itemsSubtotal, 4);
            } else {
                $this->_areTotalsValid = $itemsSubtotal > 0.00001;
            }
            $this->_areItemsValid = $this->_areItemsValid && $this->_areTotalsValid;
        }

    }
}else{
    class ITwebexperts_Itwebcommon_Model_Paypal_Cart extends Mage_Paypal_Model_Cart
    {
    }
}
