<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 * @info			http://fishpig.co.uk/wordpress-integration.html
 */

abstract class Fishpig_Wordpress_Model_Resource_Collection_Abstract extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
	/**
	 * An array of all of the meta fields that have been joined to this collection
	 *
	 * @var array
	 */
	protected $_metaFieldsJoined = array();
	
	/**
	 * Removes all order data set at the collection level
	 * This does not remove order set using self::getSelect()->order($field, $dir)
	 *
	 * @return $this
	 */
	public function resetOrderBy()
	{
		$this->_orders = array();
		
		return $this;
	}
	
	/**
	 * Add a meta field to the select statement columns section
	 *
	 * @param string $field
	 * @return $this
	 */
	public function addMetaFieldToSelect($metaKey)
	{
		if (($field = $this->_joinMetaField($metaKey)) !== false) {
			$this->getSelect()->columns(array($metaKey => $field));
		}
		
		return $this;
	}
	
	/**
	 * Add a meta field to the filter (where) part of the query
	 *
	 * @param string $field
	 * @param string|array $filter
	 * @return $this
	 */
	public function addMetaFieldToFilter($metaKey, $filter)
	{
		if (($field = $this->_joinMetaField($metaKey)) !== false) {
			$this->addFieldToFilter($field, $filter);
		}
		
		return $this;
	}
	
	/**
	 * Add a meta field to the SQL order section
	 *
	 * @param string $field
	 * @param string $dir = 'asc'
	 * @return $this
	 */
	public function addMetaFieldToSort($field, $dir = 'asc')
	{
		$this->getSelect()->order($field . ' ' . $dir);
		
		return $this;
	}
	
	/**
	 * Join a meta field to the query
	 *
	 * @param string $field
	 * @return $this
	 */
	protected function _joinMetaField($field)
	{
		$model = $this->getNewEmptyItem();
			
		if ($model->hasMeta()) {
			if (!isset($this->_metaFieldsJoined[$field])) {
				$alias = $this->_getMetaFieldAlias($field);

				$meta = new Varien_Object(array(
					'key' => $field,
					'alias' => $alias,
				));
				
				Mage::dispatchEvent($model->getEventPrefix() . '_join_meta_field', array('collection' => $this, 'meta' => $meta));
				
				if ($meta->getCanSkipJoin()) {
					$this->_metaFieldsJoined[$field] = $meta->getAlias();
				}
				else {
					$condition = "`{$alias}`.`{$model->getMetaObjectField()}`=`main_table`.`{$model->getResource()->getIdFieldName()}` AND "
						. $this->getConnection()->quoteInto("`{$alias}`.`meta_key`=?", $field);
						
					$this->getSelect()->joinLeft(array($alias => $model->getMetaTable()), $condition, '');

					$this->_metaFieldsJoined[$field] = $alias . '.meta_value';;
				}
			}
			
			return $this->_metaFieldsJoined[$field];
		}

		return false;
	}
	
	/**
	 * Convert a meta key to it's alias
	 * This is used in all SQL queries
	 *
	 * @param string $field
	 * @return string
	 */
	protected function _getMetaFieldAlias($field)
	{
		return 'meta_field_' . str_replace('-', '_', $field);
	}

	/**
	 * After loading a collection, dispatch the pre-set event
	 *
	 * @return $this
	 */
	protected function _afterLoad()
	{
		if ($this->getFlag('after_load_event_name')) {
			Mage::dispatchEvent($this->getFlag('after_load_event_name'), array(
				'collection' => $this,
				'wrapper_block' => $this->getFlag('after_load_event_block')
			));
		}

		return parent::_afterLoad();
	}
}
