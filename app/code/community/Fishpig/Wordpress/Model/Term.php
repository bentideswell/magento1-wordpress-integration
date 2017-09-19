<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_Term extends Fishpig_Wordpress_Model_Abstract
{
	/**
	 * Event data
	 *
	 * @var string
	 */
	protected $_eventPrefix = 'wordpress_term';
	protected $_eventObject = 'term';
	
	/**
	 * Entity meta infromation
	 *
	 * @var string
	 */
	protected $_metaTable = 'wordpress/term_meta';	
	protected $_metaTableObjectField = 'term_id';
	
	public function _construct()
	{
		$this->_init('wordpress/term');
	}
	
	/**
	 * Get the taxonomy object for this term
	 *
	 * @return Fishpig_Wordpress_Model_Term_Taxonomy
	 */
	public function getTaxonomyInstance()
	{
		return Mage::helper('wordpress/app')->getTaxonomy($this->getTaxonomy());
	}

	/**
	 * Retrieve the taxonomy label
	 *
	 * @return string
	 */
	public function getTaxonomyLabel()
	{
		if ($this->getTaxonomy()) {
			return ucwords(str_replace('_', ' ', $this->getTaxonomy()));
		}
		
		return false;
	}
	
	/**
	 * Retrieve the parent term
	 *
	 * @reurn false|Fishpig_Wordpress_Model_Term
	 */
	public function getParentTerm()
	{
		if (!$this->hasParentTerm()) {
			$this->setParentTerm(false);
			
			if ($this->getParentId()) {
				$parentTerm = Mage::getModel($this->getResourceName())->load($this->getParentId());
				
				if ($parentTerm->getId()) {
					$this->setParentTerm($parentTerm);
				}
			}
		}
		
		return $this->_getData('parent_term');
	}
	
	/**
	 * Retrieve a collection of children terms
	 *
	 * @return Fishpig_Wordpress_Model_Mysql_Term_Collection
	 */
	public function getChildrenTerms()
	{
		return $this->getCollection()->addParentFilter($this);
	}
	
	/**
	 * Loads the posts belonging to this category
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */    
    public function getPostCollection()
    {
		return parent::getPostCollection()
			->addIsViewableFilter()
			->addTermIdFilter($this->getChildIds(), $this->getTaxonomy());
    }
      
	/**
	 * Retrieve the numbers of items that belong to this term
	 *
	 * @return int
	 */
	public function getItemCount()
	{
		return $this->getCount();
	}
	
	/**
	 * Retrieve the parent ID
	 *
	 * @return int|false
	 */	
	public function getParentId()
	{
		return $this->_getData('parent') ? $this->_getData('parent') : false;
	}
	
	/**
	 * Retrieve the taxonomy type for this term
	 *
	 * @return string
	 */
	public function getTaxonomyType()
	{
		return $this->getTaxonomy();
	}
	
	/**
	 * Retrieve the URL for this term
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return Mage::helper('wordpress')->getUrl($this->getUri() . '/');
	}
	
	/**
	 * Retrieve the URL for this term
	 *
	 * @return string
	 */
	public function getUri()
	{
		if (!$this->hasUri()) {
			$this->setUri(
				$this->getTaxonomyInstance()->getUriById($this->getId())
			);
		}
		
		return $this->_getData('uri');
	}
	
	/**
	 * Retrieve an image URL for the category
	 * This uses the Category Images plugin (http://wordpress.org/plugins/categories-images/)
	 *
	 * @return false|string
	 */
	public function getImageUrl()
	{
		return ($imageUrl = Mage::helper('wordpress')->getWpOption('z_taxonomy_image' . $this->getId()))
			 ? $imageUrl
			 : false;
	}
	
	/**
	 * Get the children terms
	 *
	 * @deprecated - 3.2.0.0 / use self::getChildrenTerms
	 */
	public function getChildrenCategories()
	{
		return $this->getChildrenTerms();
	}
	
	/**
	 * Get the number of posts belonging to the term
	 *
	 * @return int
	 */
	public function getPostCount()
	{
		return (int)$this->getCount();
	}
	
	/**
	 * Get an array of all child ID's
	 * This includes the ID's of children's children
	 *
	 * @return array
	 */
	public function getChildIds()
	{
		if (!$this->hasChildIds()) {
			$this->setChildIds(
				$this->getResource()->getChildIds($this->getId())
			);
		}
		
		return $this->_getData('child_ids');
	}
}
