<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_List_Associated extends Fishpig_Wordpress_Block_Post_List_Wrapper_Abstract
{
	/**
	 * Set the number of posts to display to 5
	 * This can be overridden using self::setPostCount($postCount)
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_pagerLimit = 5;
		$this->setPostListTemplate('wordpress/post/associated/list.phtml');
		$this->setTitle('Blog Posts Associated With This Product');
	}

	/**
	 * Sets the number of posts to display
	 *
	 * @param string $postCount
	 */
	public function setPostCount($postCount = 5)
	{
		$this->_pagerLimit = $postCount;
		return $this;
	}

	/**
	 * Adds on cateogry/author ID filters
	 *
	 * @return array|Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	protected function _getPostCollection()
	{
		if ($this->getProduct()) {
			$collection = Mage::helper('wordpress/associations')->getAssociatedPostsByProduct($this->getProduct());
			
			if ($collection !== false) {
				return $collection->setCurPage(1);
			}
		}
		
		return false;
	}
	
	/**
	 * Get's the current product if 1 hasn't been set
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	public function getProduct()
	{
		if (!$this->hasProduct()) {
			if (!$this->hasProductId()) {
				$this->setProduct(Mage::registry('product'));
			}
			else {
				$this->setProduct(Mage::getModel('catalog/product')->load($this->getProductId()));
			}
		}
		
		return $this->getData('product');
	}
	
	/**
	 * Retrieve the base URI to be used for the pager
	 * This isn't needed for this page but has to be included anyway
	 *
	 * @return false|string
	 */
	protected function _getPagerUri()
	{
		return '/';
	}
}
