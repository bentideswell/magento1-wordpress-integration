<?php

class Fishpig_Wordpress_Block_Sidebar_Widget_Text extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return null;
	}
	
	/**
	 * Convert {{block tags to HTML
	 *
	 * @return string
	 */
	protected function _toHtml()
	{
		if ($html = parent::_toHtml()) {
			return Mage::helper('wordpress/filter')->process($html);
		}
		
		return '';
	}
}
