<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Homepage extends Fishpig_Wordpress_Block_Post_List_Wrapper_Abstract
{
	/**
	 * Get's the blog title
	 *
	 * @return string
	 */
	public function getBlogTitle()
	{
		return Mage::helper('wordpress')->getWpOption('blogname');
	}
	
	/**
	 * Retrieve the tag line set in the WordPress Admin
	 *
	 * @return string
	 */
	public function getTagLine()
	{
		return trim($this->helper('wordpress')->getWpOption('blogdescription'));
	}
	
	/**
	 * Returns the blog homepage URL
	 *
	 * @return string
	 */
	public function getBlogHomepageUrl()
	{
		return Mage::helper('wordpress')->getUrl();
	}
	
	/**
	 * Determine whether the first page of posts are being displayed
	 *
	 * @return bool
	 */
	public function isFirstPage()
	{
		return $this->getRequest()->getParam('page', '1') === '1';
	}
	
	/**
	 * Generates and returns the collection of posts
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	protected function _getPostCollection()
	{
		return parent::_getPostCollection()
			->addStickyPostsToCollection()
			->addPostTypeFilter('post');
	}
}
