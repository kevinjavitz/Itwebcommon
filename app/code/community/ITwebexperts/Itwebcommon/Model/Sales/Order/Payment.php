<?php

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


    protected function _authorizeOnly($isOnline, $amount)
    {

        // do authorization
        $order  = $this->getOrder();
        $exceptionMessage = '';
        if ($isOnline) {
            // invoke authorization on gateway
            $exceptionMessage = $this->getMethodInstance()->setStore($order->getStoreId())->authorizeOnly($this, $amount);
        }

        return $exceptionMessage;
    }

    public function authorizeOnly($isOnline, $amount)
    {
        return $this->_authorizeOnly($isOnline, $amount);
    }
}
