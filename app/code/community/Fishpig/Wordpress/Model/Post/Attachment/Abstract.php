<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Model_Post_Attachment_Abstract extends Fishpig_Wordpress_Model_Post
{
	
	protected function _afterLoad()
	{
		$this->loadSerializedData();
		
		return parent::_afterLoad();
	}
	
	/**
	 * Load the serialized attachment data
	 *
	 */
	public function loadSerializedData()
	{
		if ($this->getId() > 0 && !$this->getIsFullyLoaded()) {
			$this->setIsFullyLoaded(true);

			$select = Mage::helper('wordpress/app')->getDbConnection()
				->select()
				->from($this->getResource()->getTable('wordpress/post_meta'), 'meta_value')
				->where('meta_key=?', '_wp_attachment_metadata')
				->where('post_id=?', $this->getId())
				->limit(1);

			$data = unserialize(Mage::helper('wordpress/app')->getDbConnection()->fetchOne($select));

			if (is_array($data)) {
				foreach($data as $key => $value) {
					$this->setData($key, $value);
				}			
			}
		}
	}
	
	public function getMetaValue($key)
	{
		return parent::getMetaValue('_wp_attachment_' . $key);
	}
}
