<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Menu_Item extends Fishpig_Wordpress_Model_Resource_Post
{
	public function _construct()
	{
		$this->_init('wordpress/menu_item', 'ID');
	}
}
