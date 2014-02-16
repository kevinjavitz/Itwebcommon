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
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Recurring profiles grid
 */
class ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Grid extends Mage_Sales_Block_Adminhtml_Recurring_Profile_Grid
{

    /**
     * Prepare grid columns
     *
     * @return Mage_Sales_Block_Adminhtml_Recurring_Profile_Grid
     */
    protected function _prepareColumns()
    {
        $profile = Mage::getModel('sales/recurring_profile');

        $this->addColumn('reference_id', array(
            'header' => $profile->getFieldLabel('reference_id'),
            'index' => 'reference_id',
            'html_decorators' => array('nobr'),
            'width' => 1,
        ));
        
        $this->addColumn('customerid', array(
        	'header'=> Mage::helper('catalog')->__('customer_id'),
        	'index' => 'order_info',
        	'renderer'  => 'ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Customerid', // WE ADDED THIS
        ));

		$this->addColumn('customer_name', array(
			'header'=> Mage::helper('catalog')->__('customer_name'),
			'index' => 'order_info',
			'renderer'  => 'ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Customername', // WE ADDED THIS
		));
		
		$this->addColumn('customer_email', array(
			'header'=> Mage::helper('catalog')->__('customer_email'),
			'index' => 'order_info',
			'renderer'  => 'ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Customeremail', // WE ADDED THIS
		));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'     => Mage::helper('adminhtml')->__('Store'),
                'index'      => 'store_id',
                'type'       => 'store',
                'store_view' => true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('state', array(
            'header' => $profile->getFieldLabel('state'),
            'index' => 'state',
            'type'  => 'options',
            'options' => $profile->getAllStates(),
            'html_decorators' => array('nobr'),
            'width' => 1,
        ));

        $this->addColumn('created_at', array(
            'header' => $profile->getFieldLabel('created_at'),
            'index' => 'created_at',
            'type' => 'datetime',
            'html_decorators' => array('nobr'),
            'width' => 1,
        ));

        $this->addColumn('updated_at', array(
            'header' => $profile->getFieldLabel('updated_at'),
            'index' => 'updated_at',
            'type' => 'datetime',
            'html_decorators' => array('nobr'),
            'width' => 1,
        ));
        
        $this->addColumn('next_cycle', array(
        	'header'=> Mage::helper('catalog')->__('Next Bill'),
        	'index' => 'additional_info',
        	'type' => 'datetime',
        	'html_decorators' => array('nobr'),
        	'width' => 1,
        	'renderer'  => 'ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Nextcycle', // WE ADDED THIS
        ));

        $methods = array();
        foreach (Mage::helper('payment')->getRecurringProfileMethods() as $method) {
            $methods[$method->getCode()] = $method->getTitle();
        }
        $this->addColumn('method_code', array(
            'header'  => $profile->getFieldLabel('method_code'),
            'index'   => 'method_code',
            'type'    => 'options',
            'options' => $methods,
        ));

        $this->addColumn('schedule_description', array(
            'header' => $profile->getFieldLabel('schedule_description'),
            'index' => 'schedule_description',
        ));
        
        $this->addColumn('coupon_code', array(
        	'header'=> Mage::helper('catalog')->__('coupon_code'),
        	'index' => 'order_info',
        	'renderer'  => 'ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Couponused', // WE ADDED THIS
        ));
        
        $this->addColumn('additional_info', array(
        	'header'=> Mage::helper('catalog')->__('additional_info'),
        	'index' => 'additional_info',
        	'renderer'  => 'ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Additional', // WE ADDED THIS
        ));

        return parent::_prepareColumns();
    }
}
