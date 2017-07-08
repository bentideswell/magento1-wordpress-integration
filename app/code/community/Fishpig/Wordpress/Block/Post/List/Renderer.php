<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_List_Renderer extends Fishpig_Wordpress_Block_Post_Abstract
{
	/**
	 * Retrieve the correct block to prepare posts
	 *
	 * @return Fishpig_Wordpress_Block_Post_List
	 */
	protected function _getBlockForPostPrepare()
	{
		return $this->getParentBlock();
	}
}
