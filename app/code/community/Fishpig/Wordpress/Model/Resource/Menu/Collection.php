<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Menu_Collection extends Fishpig_Wordpress_Model_Resource_Term_Collection
{
	public function _construct()
	{
		$this->_init('wordpress/menu');
	}
	
	/**
	 * Filter the term collection so that only nav_menu's are included
	 *
	 * @return $this
	 */
	protected function _initSelect()
	{
		parent::_initSelect();
		
		$this->_orders = array();
		
		$this->getSelect()->where('taxonomy.taxonomy=?', $this->getNewEmptyItem()->getTaxonomy());
			
		return $this;
	}
}
