<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_Term_Taxonomy extends Varien_Object
{
	/**
	 * Get the URI's that apply to $uri
	 *
	 * @param string $uri = ''
	 * @return array|false
	 */
	public function getUris($uri = '')
	{
		if ($uri && $this->getSlug() && strpos($uri, $this->getSlug()) === false) {
			return false;
		}
		
		return $this->getAllUris();
	}
	
	/**
	 * Get all of the URI's for this taxonomy
	 *
	 * @return array|false
	 */
	public function getAllUris()
	{
		if ($this->hasAllUris()) {
			return $this->_getData('all_uris');
		}
		
		$this->setAllUris(false);
		
		$helper = Mage::helper('wordpress/app');
		$db = $helper->getDbConnection();
		
		$select = $db->select()
			->from(array('term' => $helper->getTableName('wordpress/term')), array(
				'id' => 'term_id', 
				'url_key' => 'slug',new Zend_Db_Expr("TRIM(LEADING '/' FROM CONCAT('" . rtrim($this->getSlug(), '/') . "/', slug))"),
				))
			->join(
				array('tax' => $helper->getTableName('wordpress/term_taxonomy')),
				$db->quoteInto("tax.term_id = term.term_id AND tax.taxonomy = ?", $this->getTaxonomyType()),
				'parent'
			);

		if ($results = $db->fetchAll($select)) {
			if ((bool)Mage::getConfig()->getNode('wordpress/legacy/disable_term_hierarchy')) {
				foreach($results as $key => $result) {
					$results[$key]['parent'] = null;
				}
			}

			$this->setAllUris(Mage::helper('wordpress/router')->generateRoutesFromArray($results, $this->getSlug()));
		}

		return $this->_getData('all_uris');
	}

	/**
	 * Retrieve the URI for $term
	 *
	 * @param Fishpig_Wordpress_Model_Term $term
	 * @return false|string
	 */
	public function getUriById($id, $includePrefix = true)
	{
		if (($uris = $this->getAllUris()) !== false) {
			if (isset($uris[$id])) {
				$uri = $uris[$id];

				if (!$includePrefix && $this->getSlug() && strpos($uri, $this->getSlug() . '/') === 0) {
					$uri = substr($uri, strlen($this->getSlug())+1);
				}
				
				return $uri;
			}
		}

		return false;
	}

	/**
	 * Determine whether the taxonomy uses a hierarchy in it's link
	 *
	 * @return  bool
	 */
	public function isHierarchical()
	{
		return (int)$this->getData('hierarchical') === 1;
	}
	
	/**
	 * Get the taxonomy slug
	 *
	 * @return string
	 */
	public function getSlug()
	{
		return trim($this->getData('rewrite/slug'), '/');
	}
	
	public function setSlug($slug)
	{
		if (!isset($this->_data['rewrite'])) {
			$this->_data['rewrite'] = array();
		}
		
		$this->_data['rewrite']['slug'] = $slug;
		
		return $this;
	}
	
	/**
	 * Get a collection of terms that belong this taxonomy and $post
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return Fishpig_Wordpress_Model_Resource_Post_Collection
	 */
	public function getPostTermsCollection(Fishpig_Wordpress_Model_Post $post)
	{
		return Mage::getResourceModel('wordpress/term_collection')
			->addTaxonomyFilter($this->getTaxonomyType())
			->addPostIdFilter($post->getId());
	}
}
