<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Catalog_Product extends Fishpig_Wordpress_Helper_Abstract
{
	/**
	 * Returns a collection of Wordpress posts that have been
	 * associated with the given product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	public function getAssociatedPosts($product)
	{
		if ($product instanceof Mage_Catalog_Model_Product) {
			$postIds = $this->getAssociatedPostIds($product);
			$categoryIds = $this->getAssociatedCategoryIds($product);

			if (count($postIds) > 0 || count($categoryIds) > 0) {
				$collection = Mage::getResourceModel('wordpress/post_collection')->addIsViewableFilter();
				$collection->getSelect()->distinct();
				$collection->addCategoryAndPostIdFilter($postIds, $categoryIds);
				$collection->setOrderByPostDate();
			
				return $collection;
			}
		}
		
		return false;
	}

	/**
	 * Retrieve an array of post_ids that are associated with the product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return array
	 */
	public function getAssociatedPostIds(Mage_Catalog_Model_Product $product)
	{
		$postIds = $this->_getAssociatedWpEntityIds($product->getId(), 'post', 'post');

		if ($categoryIds = $product->getCategoryIds()) {
			foreach($categoryIds as $categoryId) {
				$postIds = array_merge($postIds, $this->_getAssociatedWpEntityIds($categoryId, 'post', 'post', 'category_id', 'category'));
			}
		}
		
		return array_unique($postIds);
	}

	/**
	 * Retrieve an array of category_ids that are associated with the product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return array
	 */	
	public function getAssociatedCategoryIds(Mage_Catalog_Model_Product $product)
	{
		$wpCategoryIds = $this->_getAssociatedWpEntityIds($product->getId(), 'category', 'category');
		
		if ($categoryIds = $product->getCategoryIds()) {
			foreach($categoryIds as $categoryId) {
				$wpCategoryIds = array_merge($wpCategoryIds, $this->_getAssociatedWpEntityIds($categoryId, 'wp_category', 'category', 'category_id', 'category'));
			}
		}
		
		return array_unique($wpCategoryIds);
	}

	/**
	 * Retrieve an array of category_ids/post_ids that are associated with the product
	 *
	 * @return array
	 */		
	protected function _getAssociatedWpEntityIds($id, $field, $table, $fieldToMatch = 'product_id', $magentoEntity = 'product')
	{
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');	
		$select = $read->select()
			->distinct(true)
			->from(Mage::getSingleton('core/resource')->getTableName('wordpress_' . $magentoEntity . '_' . $table), "{$field}_id")
			->where("{$fieldToMatch} = ?", $id)
			->where('store_id=?', Mage::app()->getStore()->getId());

		return $read->fetchCol($select);
	}
	
	/** 
	 * Retrieve a collection of products assocaited with the post
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
	 */
	public function getAssociatedProducts($post)
	{
		if ($post instanceof Fishpig_Wordpress_Model_Post) {
			$productIds = $this->_getAssociatedWpEntityIds($post->getId(), 'product', 'post', 'post_id');
			
			try {
				foreach($post->getParentCategories() as $category) {
					$productIds = array_merge($productIds, $this->_getAssociatedWpEntityIds($category->getId(), 'product', 'category', 'category_id'));
				}
			}
			catch (Exception $e) {
				$this->log($e->getMessage());
			}

			if (count($productIds) > 0) {
				$collection = Mage::getResourceModel('catalog/product_collection');		
				Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
				$collection->addAttributeToFilter('status', 1);
				$collection->addAttributeToFilter('entity_id', array('in' => $productIds));
			
				return $collection;
			}
		}
		
		return false;
	}
}
