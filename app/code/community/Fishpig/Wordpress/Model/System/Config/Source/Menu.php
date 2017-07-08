<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_System_Config_Source_Menu
{
	protected $_options = null;
	
	public function toOptionArray()
	{
		if (is_null($this->_options)) {
			$this->_options = array(array(
				'value' => '',
				'label' => Mage::helper('adminhtml')->__('-- Please Select --')
			));
			
			if (Mage::helper('wordpress/app')->getDbConnection()) {
				$menus = Mage::getResourceModel('wordpress/menu_collection')->load();
				
				foreach($menus as $menu) {
					$this->_options[] = array('value' => $menu->getId(), 'label' => $menu->getName());
				}
			}
		}
		
		return $this->_options;
	}
}
