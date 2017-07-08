<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Image_Collection extends Fishpig_Wordpress_Model_Resource_Post_Attachment_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('wordpress/image');
	}
	
	/**
	 * Load an image
	 * Ensure that only images are returned
	 *
	 * @param bool $printQuery
	 * @param bool $logQuery
	 * @return $this
	 */
    public function load($printQuery = false, $logQuery = false)
    {
		$this->getSelect()->where("post_mime_type LIKE 'image%'");
		
		return parent::load($printQuery, $logQuery);
    }
}
