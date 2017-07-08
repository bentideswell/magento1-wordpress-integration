<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Image extends Fishpig_Wordpress_Model_Resource_Post_Attachment_Abstract
{
	public function _construct()
	{
		$this->_init('wordpress/image', 'ID');
	}

	public function isImagePostName($postName)
	{
		$select = $this->_getReadAdapter()
			->select()
			->from($this->getMainTable(), 'ID')
			->where('post_type=?', 'attachment')
			->where('post_name=?', $postName)
			->limit(1);
			
		return $this->_getReadAdapter()->fetchOne($select);
	}
}
