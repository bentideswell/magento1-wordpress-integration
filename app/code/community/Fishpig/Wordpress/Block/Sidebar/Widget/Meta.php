<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Sidebar_Widget_Meta extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->__('Meta');
	}
	
	/**
	 * Determine whether the current customer is logged in
	 *
	 * @return bool
	 */
	public function customerIsLoggedIn()
	{
		return Mage::getSingleton('customer/session')->isLoggedIn();
	}
}
