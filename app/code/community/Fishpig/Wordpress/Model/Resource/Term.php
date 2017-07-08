<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Term extends Fishpig_Wordpress_Model_Resource_Abstract
{
	/**
	 * Determine whether there is a term order field
	 *
	 * @static bool
	 */
	protected static $_tableHasTermOrder = null;
	
	public function _construct()
	{
		$this->_init('wordpress/term', 'term_id');
	}
	
	/**
	 * Custom load SQL to combine required tables
	 *
	 * @param string $field
	 * @param string|int $value
	 * @param Mage_Core_Model_Abstract $object
	 */
	protected function _getLoadSelect($field, $value, $object)
	{
		$select = $this->_getReadAdapter()->select()
			->from(array('main_table' => $this->getMainTable()));
		
		if (strpos($field, '.') !== false) {
			$select->where($field . '=?', $value);
		}
		else {
			$select->where("main_table.{$field}=?", $value);
		}
			
		$select->join(
			array('taxonomy' => $this->getTable('wordpress/term_taxonomy')),
			'`main_table`.`term_id` = `taxonomy`.`term_id`',
			array('term_taxonomy_id', 'taxonomy', 'description', 'count', 'parent')
		);
		
		if ($object->getTaxonomy()) {
			$select->where('taxonomy.taxonomy=?', $object->getTaxonomy());
		}

		return $select->limit(1);
	}
	
	/**
	 * Determine whether a 'term_order' field is present
	 *
	 * @return bool
	 */
	public function tableHasTermOrderField()
	{
		if (!is_null(self::$_tableHasTermOrder)) {
			return self::$_tableHasTermOrder;
		}
		
		try {
			self::$_tableHasTermOrder = $this->_getReadAdapter()
				->fetchOne('SHOW COLUMNS FROM ' . $this->getMainTable() . ' WHERE Field = \'term_order\'')
				!== false;
		}
		catch (Exception $e) {
			self::$_tableHasTermOrder = false;
		}
		
		return self::$_tableHasTermOrder;
	}
	
	/**
	 * Get all child ID's for a parent
	 * This includes recursive levels
	 *
	 * @param int $parentId
	 * @return array
	 */
	public function getChildIds($parentId)
	{
		$select = $this->_getReadAdapter()
			->select()
				->from($this->getTable('wordpress/term_taxonomy'), 'term_id')
				->where('parent=?', $parentId)
				->where('count>?', 0);
		
		if ($termIds = $this->_getReadAdapter()->fetchCol($select)) {
			foreach($termIds as $key => $termId) {
				$termIds = array_merge($termIds, $this->getChildIds($termId));
			}
			
			return array_unique(array_merge(array($parentId), $termIds));
		}
		
		return array($parentId);
	}
}
