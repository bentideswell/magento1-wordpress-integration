<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Term_View extends Fishpig_Wordpress_Block_Post_List_Wrapper_Abstract
{
	/**
	 * Returns the current Wordpress category
	 * This is just a wrapper for getCurrentCategory()
	 *
	 * @return Fishpig_Wordpress_Model_Post_Categpry
	 */
	public function getTerm()
	{
		if (!$this->hasTerm()) {
			$this->setTerm(Mage::registry('wordpress_term'));
		}
		
		return $this->_getData('term');
	}
	
	/**
	 * Generates and returns the collection of posts
	 *
	 * @return Fishpig_Wordpress_Model_Resource_Post_Collection
	 */
	protected function _getPostCollection()
	{
		if ($this->getTerm()) {
			return $this->getTerm()->getPostCollection();
		}
		
		return false;
	}
}
