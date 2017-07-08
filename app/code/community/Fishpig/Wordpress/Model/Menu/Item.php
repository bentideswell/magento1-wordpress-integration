<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_Menu_Item extends Fishpig_Wordpress_Model_Post
{
	/**
	 * Link types used to determine menu item functionality
	 *
	 * @const string
	 */
	const LINK_TYPE_CUSTOM = 'custom';
	const LINK_TYPE_POST_TYPE = 'post_type';
	const LINK_TYPE_TAXONOMY = 'taxonomy';
	
	/**
	 * Prefix of model events names
	 *
	 * @var string
	*/
	protected $_eventPrefix = 'wordpress_menu_item';
	
	/**
	 * Parameter name in event
	 *
	 * @var string
	*/
	protected $_eventObject = 'menu_item';

	
	public function _construct()
	{
		$this->_init('wordpress/menu_item');
	}
	
	/**
	 * Retrieve the post type for this post
	 *
	 * @return string
	 */
	public function getPostType()
	{
		return 'nav_menu_item';
	}
	
	/**
	 * Determine whether the link is a custom link
	 *
	 * @return bool
	 */
	public function isCustomLink()
	{
		return $this->getItemType() === self::LINK_TYPE_CUSTOM;
	}
	
	/**
	 * Determine whether the object type is a post_type
	 *
	 * @return bool
	 */
	public function isPostTypeLink()
	{
		return $this->getItemType() === self::LINK_TYPE_POST_TYPE;
	}
	
	/**
	 * Determine whether the object type is a post_type
	 *
	 * @return bool
	 */
	public function isTaxonomyLink()
	{
		return $this->getItemType() === self::LINK_TYPE_TAXONOMY;
	}
	
	/**
	 * Retrieve the link object
	 *
	 * @return false|Fishpig_Wordpress_Model_Abstract
	 */
	public function getObject()
	{

		$this->setObject(false);

		if (!$this->isCustomLink()) {
			if ($this->getObjectType()) {
				if ($menuObjectId = $this->getMetaValue('_menu_item_object_id')) {
					if ($this->isPostTypeLink())  {
						$object = Mage::getModel('wordpress/post')->setPostType($this->getObjectType());
					}
					else if ($this->isTaxonomyLink()) {
						$object = Mage::getModel('wordpress/term')->setTaxonomy($this->getObjectType());
					}
					else {
						$object = Mage::getModel('wordpress/' . $this->getObjectType());
					}
				
					if ($object && $object->setSkipObjectCache(true)->load($menuObjectId)->getId()) {
						$this->setObject($object);
					}
				}
			}
		}
		
		return $this->_getData('object');
	}

	/**
	 * Retrieve the menu item type
	 *
	 * @return string
	 */
	public function getItemType()
	{
		return $this->getMetaValue('_menu_item_type');
	}
	
	/**
	 * Retrieve the object type
	 *
	 * @return string
	 */
	public function getObjectType()
	{
		if (!$this->_getData('object_type')) {
			$this->setObjectType($this->getMetaValue('_menu_item_object'));
		}
		
		return $this->_getData('object_type');
	}

	/**
	 * Retrieve the URL for the link
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if ($this->isCustomLink()) {
			return $this->getMetaValue('_menu_item_url');
		}
		else if ($this->getObject() !== false) {
			if (in_array($this->getObjectType(), array('page', 'post'))) {
				return $this->getObject()->getPermalink();
			}
			else {
				return $this->getObject()->getUrl();
			}
		}
	}
	
	/**
	 * Retrieve the link label
	 *
	 * @return string
	 */
	public function getLabel()
	{
		if ($this->getPostTitle() || $this->isCustomLink()) {
			return $this->getPostTitle();
		}
		else if ($this->isPostTypeLink() && $this->getObject()) {
			return $this->getObject()->getPostTitle();
		}
		else if ($this->isTaxonomyLink() && $this->getObject()) {
			return $this->getObject()->getName();
		}
	}
	
	/**
	 * Determine whether the link is active
	 *
	 * @return bool
	 */
	public function isItemActive()
	{
		$currentUrl = Mage::getUrl('*/*/*', array('_current' => true, '_use_rewrite' => true));
		
		if (strpos($currentUrl, '?') !== false) {
			$currentUrl = substr($currentUrl, 0, strpos($currentUrl, '?'));
		}
		
		return $currentUrl === $this->getUrl();
	}
	
	/**
	 * Retrieve children menu items
	 *
	 * @return Fishpig_Wordpress_Model_Resource_Menu_Item_Collection
	 */
	public function getChildrenItems()
	{
		return Mage::getResourceModel('wordpress/menu_item_collection')
			->addParentItemIdFilter($this->getId());
	}
}
