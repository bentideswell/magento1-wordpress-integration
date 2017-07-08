<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Menu_Item_Collection extends Fishpig_Wordpress_Model_Resource_Post_Collection
{
	/**
	 * Name prefix of events that are dispatched by model
	 *
	 * @var string
	*/
	protected $_eventPrefix = 'wordpress_menu_item_collection';
	
	/**
	 * Name of event parameter
	 *
	 * @var string
	*/
	protected $_eventObject = 'menu_items';
	
	/**
	 * Initialise the object
	 *
	 */
	public function _construct()
	{
		$this->_init('wordpress/menu_item');
		
		$this->addPostTypeFilter('nav_menu_item');
	}
	
	/**
	 * Ensures that only posts and not pages are returned
	 * WP stores posts and pages in the same DB table
	 *
	 */
    protected function _initSelect()
    {
    	parent::_initSelect();

		$this->getSelect()->order('menu_order ASC');

		return $this;
	}
	
	/**
	 * Filter the collection by parent ID
	 * Set 0 as $parentId to return root menu items
	 *
	 * @param int $parentId = 0
	 * @return $this
	 */
	public function addParentItemIdFilter($parentId = 0)
	{
		return $this->addMetaFieldToFilter('_menu_item_menu_item_parent', $parentId);
	}
}
