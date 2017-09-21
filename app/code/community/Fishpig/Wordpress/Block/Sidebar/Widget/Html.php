<?php

class Fishpig_Wordpress_Block_Sidebar_Widget_Html extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Set the posts collection
	 *
	 */
	protected function _beforeToHtml()
	{
		parent::_beforeToHtml();

		if (!$this->getTemplate()) {
			$this->setTemplate('wordpress/sidebar/widget/html.phtml');
		}

		return $this;
	}
	
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
	public function getHtml()
	{
		if ($html = $this->getContent()) {
			return Mage::helper('wordpress/filter')->process($html);
		}
		
		return '';	
	}
}
