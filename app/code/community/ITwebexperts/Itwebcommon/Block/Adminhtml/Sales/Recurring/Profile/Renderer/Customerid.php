<?php
class ITwebexperts_Itwebcommon_Block_Adminhtml_Sales_Recurring_Profile_Renderer_Customerid extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
	
		$value =  $row->getData($this->getColumn()->getIndex());
		
		$value = unserialize($value);

		return $value['customer_id'];
	
	}
}
?>