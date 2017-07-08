<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Sidebar_Widget_Search extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Retrieve the action URL for the search form
	 *
	 * @return string
	 */
	public function getFormActionUrl()
	{
		return $this->helper('wordpress')->getUrl(
			$this->helper('wordpress/router')->getSearchRoute()
		) . '/';
	}
	
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->__('Search');
	}
	
	/**
	 * Retrieve the search term used
	 *
	 * @return string
	 */
	public function getSearchTerm()
	{
		return $this->helper('wordpress/router')->getSearchTerm();
	}
	
	/**
	 * Ensure template is set
	 *
	 * @return string
	 */
	protected function _beforeToHtml()
	{
		if (!$this->getTemplate()) {
			$this->setTemplate('wordpress/sidebar/widget/search.phtml');
		}
		
		return parent::_beforeToHtml();
	}
}
