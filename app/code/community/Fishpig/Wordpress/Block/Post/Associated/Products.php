<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_Associated_Products extends Mage_Catalog_Block_Product_Abstract
{
	/**
	 * Retrieve a collection of products
	 *
	 * @return array|Mage_Catalog_Model_Mysql4_Resource_Eav_Mysql4_Product_Collection
	 */
	public function getProducts($attributes = null)
	{
		if ($this->getPost() !== null) {
			$collection = Mage::helper('wordpress/associations')->getAssociatedProductsByPost($this->getPost());

			if ($collection !== false) {
				if (is_null($attributes)) {
					$attributes = Mage::getSingleton('catalog/config')->getProductAttributes();
				}
				
				if ($this->getCount()) {
					$collection->setPageSize($this->getCount())
						->setCurPage(1);
				}
				


				return $collection->addAttributeToSelect($attributes);
			}
		}

		return array();
	}
	
	/**
	 * Retrieve the post object
	 *
	 * @return false|Fishpig_Wordpress_Model_Post
	 */
	public function getPost()
	{
		return Mage::registry('wordpress_post');
	}
}
