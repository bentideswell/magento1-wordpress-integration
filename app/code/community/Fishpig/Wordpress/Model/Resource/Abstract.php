<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 * @info			http://fishpig.co.uk/wordpress-integration.html
 */

abstract class Fishpig_Wordpress_Model_Resource_Abstract extends Mage_Core_Model_Resource_Db_Abstract
{
	/**
	 * Retrieve the appropriate read adapter
	 *
	 * @return
	 */
	protected function _getReadAdapter()
	{
		return Mage::helper('wordpress/app')->getDbConnection();
	}

	/**
	 * Retrieve the appropriate write adapter
	 *
	 * @return
	 */	
	protected function _getWriteAdapter()
	{
		return Mage::helper('wordpress/app')->getDbConnection();
	}
	
	/**
	 * Retrieve a meta value from the database
	 * This only works if the model is setup to work a meta table
	 * If not, null will be returned
	 *
	 * @param Fishpig_Wordpress_Model_Meta_Abstract $object
	 * @param string $metaKey
	 * @param string $selectField
	 * @return null|mixed
	 */
	public function getMetaValue(Fishpig_Wordpress_Model_Abstract $object, $metaKey, $selectField = 'meta_value')
	{
		if ($object->hasMeta()) {
			$select = $this->_getReadAdapter()
				->select()
				->from($object->getMetaTable(), $selectField)
				->where($object->getMetaObjectField() . '=?', $object->getId())
				->where('meta_key=?', $metaKey)
				->limit(1);

			if (($value = $this->_getReadAdapter()->fetchOne($select)) !== false) {
				return trim($value);
			}
			
			return false;
		}
		
		return null;
	}

	/**
	 * Save a meta value to the database
	 * This only works if the model is setup to work a meta table
	 *
	 * @param Fishpig_Wordpress_Model_Meta_Abstract $object
	 * @param string $metaKey
	 * @param string $metaValue
	 */
	public function setMetaValue(Fishpig_Wordpress_Model_Abstract $object, $metaKey, $metaValue)
	{
		if ($object->hasMeta()) {
			$metaValue = trim($metaValue);
			$metaData = array(
				$object->getMetaObjectField() => $object->getId(),
				'meta_key' => $metaKey,
				'meta_value' => $metaValue,
			);
							
			if (($metaId = $this->getMetaValue($object, $metaKey, $object->getMetaPrimaryKeyField())) !== false) {
				$this->_getWriteAdapter()->update($object->getMetaTable(), $metaData, $object->getMetaPrimaryKeyField() . '=' . $metaId);
			}
			else {
				$this->_getWriteAdapter()->insert($object->getMetaTable(), $metaData);
			}
		}
	}
	
	/**
	 * Get an array of all of the meta values associated with this post
	 *
	 * @param Fishpig_Wordpress_Model_Meta_Abstract $object
	 * @return false|array
	 */
	public function getAllMetaValues(Fishpig_Wordpress_Model_Meta_Abstract $object)
	{
		if ($object->hasMeta()) {
			$select = $this->_getReadAdapter()
				->select()
				->from($object->getMetaTable(), array('meta_key', 'meta_value'))
				->where($object->getMetaObjectField() . '=?', $object->getId());

			if (($values = $this->_getReadAdapter()->fetchPairs($select)) !== false) {
				return $values;
			}
		}
		
		return false;
	}
}
