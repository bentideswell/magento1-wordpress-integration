<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Associations extends Fishpig_Wordpress_Helper_Abstract
{
	public function isConnected()
	{
		return Mage::helper('wordpress/app')->getDbConnection() !== false;
	}
	
	/**
	 * Retrieve a collection of post's associated with the given product
	 *
	 * @param Mage_Catalog_Model_Product $product
	 * @return false|Fishpig_Wordpress_Model_Resource_Post_Collection
	 */
	public function getAssociatedPostsByProduct(Mage_Catalog_Model_Product $product)
	{
		if (!$this->isConnected() || !($product instanceof Mage_Catalog_Model_Product)) {
			return false;
		}
		
		$associations = array_keys($this->getAssociations('product/post', $product->getId()));
		$categoryAssociations = array_keys($this->getAssociations('product/category', $product->getId()));
		$associations = array_merge($associations, $this->_convertWpCategoryIds($categoryAssociations));
		
		foreach($product->getCategoryIds() as $categoryId) {
			$associations = array_merge($associations, array_keys($this->getAssociations('category/post', $categoryId)));
			$categoryAssociations = array_keys($this->getAssociations('category/category', $categoryId));
			$associations = array_merge($associations, $this->_convertWpCategoryIds($categoryAssociations));
		}
		
		if (count($associations) > 0) {
			return Mage::getResourceModel('wordpress/post_collection')
				->addFieldToFilter('ID', array('IN' => $associations))
				->addIsViewableFilter();
		}
		
		return false;
	}

	/**
	 * Retrieve a collection of post's associated with the given product
	 *
	 * @param Mage_Cms_Model_Page $page
	 * @return false|Fishpig_Wordpress_Model_Resource_Post_Collection
	 */
	public function getAssociatedPostsByCmsPage(Mage_Cms_Model_Page $page)
	{
		if (!$this->isConnected() || !($page instanceof Mage_Cms_Model_Page)) {
			return false;
		}

		$associations = array_keys($this->getAssociations('cms_page/post', $page->getId()));
		$categoryAssociations = array_keys($this->getAssociations('cms_page/category', $page->getId()));
		$associations = array_merge($associations, $this->_convertWpCategoryIds($categoryAssociations));

		if (count($associations) > 0) {
			return Mage::getResourceModel('wordpress/post_collection')
				->addFieldToFilter('ID', array('IN' => $associations))
				->addIsViewableFilter();
		}
		
		return false;
	}

	/** 
	 * Retrieve a collection of products assocaited with the post
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
	 */
	public function getAssociatedProductsByPost(Fishpig_Wordpress_Model_Post $post)
	{
		if (!$this->isConnected() || !($post instanceof Fishpig_Wordpress_Model_Post)) {
			return false;
		}

		$associations = array_keys($this->getReverseAssociations('product/post', $post->getId()));

		foreach($post->getParentCategories() as $category) {
			$associations = array_merge($associations, array_keys($this->getReverseAssociations('product/category', $category->getId())));
		}

		$associations = array_unique($associations);

		if (count($associations) > 0) {
			$collection = Mage::getResourceModel('catalog/product_collection');
				
			Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
			
			if (!Mage::getStoreConfigFlag('cataloginventory/options/show_out_of_stock')) {
				Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			}

			$collection->addAttributeToFilter('status', 1);
			$collection->addAttributeToFilter('entity_id', array('in' => $associations));
		

			return $collection;
		}
		
		return false;
	}

	/**
	 * Retrieve an array of associated ID's and their position value
	 *
	 * @param string $type
	 * @param int $objectId
	 * @param int|null $storeId = null
	 * @return array|false
	 */
	public function getAssociations($type, $objectId, $storeId = null)
	{
		return $this->_getAssociations($type, $objectId, $storeId, 'object_id', 'wordpress_object_id');
	}
	
	/**
	 * Retrieve an array of associated ID's and their position value
	 * This receives a post ID and returns the associated Magento product ID's
	 *
	 * @param string $type
	 * @param int $objectId
	 * @param int|null $storeId = null
	 * @return array
	 */
	public function getReverseAssociations($type, $objectId, $storeId = null)
	{
		return $this->_getAssociations($type, $objectId, $storeId, 'wordpress_object_id', 'object_id');
	}
	
	/**
	 * Retrieve an array of associated ID's and their position value
	 *
	 * @param string $type
	 * @param int $objectId
	 * @param int|null $storeId = null
	 * @return array
	 */
	protected function _getAssociations($type, $objectId, $storeId = null, $filter, $return)
	{
		try {
			if (($typeId = $this->getTypeId($type)) !== false) {
				if (is_null($storeId)) {
					$storeId = Mage::app()->getStore()->getId();
				}
	
				$select = $this->_getReadAdapter()
					->select()
					->from($this->_getTable('wordpress/association'), array($return, 'position'))
					->where('type_id=?', $typeId)
					->where($filter . '=?', $objectId)
					->where('store_id=?', $storeId)
					->order('position ASC');
	
				if (($results = $this->_getReadAdapter()->fetchAll($select)) !== false) {
					$associations = array();
					
					foreach($results as $result) {
						$associations[$result[$return]] = $result['position'];
					}
	
					return $associations;
				}
			}
		}
		catch (Exception $e) {
			$this->log($e);
		}
		
		return array();
	}

	/**
	 * Delete all associations for a type/object_id/store combination
	 *
	 * @param string $type
	 * @param int $objectId
	 * @param int|null $storeId = null
	 * @return $this
	 */
	public function deleteAssociations($type, $objectId, $storeId = null)
	{	
		if (($typeId = $this->getTypeId($type)) !== false) {
			if (is_null($storeId)) {
				$storeId = Mage::app()->getStore()->getId();
			}
			
			$write = $this->_getWriteAdapter();
			$table = $this->_getTable('wordpress/association');
			
			$cond = implode(' AND ', array(
				$write->quoteInto('object_id=?', $objectId),
				$write->quoteInto('type_id=?', $typeId),
				$write->quoteInto('store_id=?', $storeId),
			));

			$write->delete($table, $cond, $storeId);
			$write->commit();
		}
		
		return $this;
	}
	
	/**
	 * Create associations
	 *
	 * @param string $type
	 * @param int $objectId
	 * @param array $associations
	 * @param int|null $storeId = null
	 * @return $this
	 */
	public function createAssociations($type, $objectId, array $associations, $storeId = null)
	{
		if (count($associations) === 0) {
			return $this;
		}
		
		if (($typeId = $this->getTypeId($type)) !== false) {
			if (is_null($storeId)) {
				$storeId = Mage::app()->getStore()->getId();
			}
			
			$write = $this->_getWriteAdapter();
			$table = $this->_getTable('wordpress/association');

			foreach($associations as $wpObjectId => $data) {
				if (is_array($data)) {
					$position = array_shift($data);
				}
				else {
					$position = 0;
					$wpObjectId = $data;
				}
				
				$write->insert($table, array(
					'type_id' => $typeId,
					'object_id' => $objectId,
					'wordpress_object_id' => $wpObjectId,
					'position' => $position,
					'store_id' => $storeId,
				));

				$write->commit();
			}
		}
		
		return $this;
	}

	/**
	 * Process an observer triggered to save associations
	 * This only works for certain models
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */
	public function processObserver(Varien_Event_Observer $observer)
	{
		$storeIds = $this->getAssociationStoreIds();

		if (($product = $observer->getEvent()->getProduct()) !== null) {
			$objectId = $product->getId();
		}
		else if (($category = $observer->getEvent()->getCategory()) !== null) {
			$objectId = $category->getId();
		}
		else if ($observer->getEvent()->getObject() instanceof Mage_Cms_Model_Page) {
			$objectId = $observer->getEvent()->getObject()->getId();
#			$storeIds = array(0);
		}
		else {
			return false;
		}

		$post = Mage::app()->getRequest()->getPost('wp');
		
		if (!isset($post['assoc'])) {
			return false;
		}
		
		$assocData = $post['assoc'];

		foreach($assocData as $object => $data) {
			foreach($data as $wpObject => $associations) {
				$associations = Mage::helper('adminhtml/js')->decodeGridSerializedInput($associations);
				$type = $object . '/' . $wpObject;
		
				foreach($storeIds as $storeId) {
					$this->deleteAssociations($type, $objectId, $storeId)->createAssociations($type, $objectId, $associations, $storeId);
				}
			}
		}
	}
	
	/**
	 * Retrieve a type_id associated with the given type
	 *
	 * @param string $type
	 * @return int|false
	 */
	public function getTypeId($type)
	{
		if (strpos($type, '/') !== false) {
			$types = explode('/', $type);
			
			$select = $this->_getReadAdapter()
				->select()
				->from($this->_getTable('wordpress/association_type'), 'type_id')
				->where('object=?', $types[0])
				->where('wordpress_object=?', $types[1])
				->limit(1);
				
			return $this->_getReadAdapter()->fetchOne($select);
		}
		
		return false;
	}

	/**
	 * Add the position value for the association between each item and the $type and $objectId
	 * combination
	 *
	 * @param $collection
	 * @param string $type
	 * @param int $objectId
	 * @return $this
	 */	
	public function addRelatedPositionToSelect($collection, $type, $objectId, $storeId = null)
	{
		if (($typeId = Mage::helper('wordpress/associations')->getTypeId($type)) !== false) {
			if (is_null($storeId)) {
				$storeId = array((int)Mage::app()->getStore()->getId(), 0);
			}
			else if (!is_array($storeId)) {
				$storeId = array($storeId, 0);
			}
			
			$field = strpos($type, '/category') !== false ? 'term_id' : 'ID';

			$cond = implode(' AND ', array(
				'`assoc`.`wordpress_object_id` = `main_table`.`' . $field . '`',
				'`assoc`.`object_id` = ' . (int)$objectId,
				'`assoc`.`type_id` = ' . (int)$typeId,
				Mage::getSingleton('core/resource')->getConnection('core_read')->quoteInto('`assoc`.`store_id` IN (?)', $storeId),
			));
			
			$dbname = $this->_getTable('wordpress/association');

			if (!Mage::getStoreConfigFlag('wordpress/database/is_shared')) {
				$dbname = (string)Mage::getConfig()->getNode('global/resources/default_setup/connection/dbname') . '.' . $dbname;
			}
			
			$collection->getSelect()
				->distinct()
				->joinLeft(
					array('assoc' => $dbname),
					$cond, 
					array('associated_position' => 'IF(ISNULL(position), 4444, position)', 'is_associated' => 'IF(ISNULL(assoc.position), 0, 1)')
				);
				
			$collection->getSelect()->order('assoc.store_id DESC');
		}
		
		return $this;
	}

	/**
	 * Retrieve the current store ID
	 * If no store ID is set or site is multistore, return default store ID
	 *
	 * @return int
	 */
	public function getAssociationStoreIds()
	{
		$singleStore = Mage::app()->isSingleStoreMode() && Mage::helper('wordpress')->forceSingleStore();
			
		if (!$singleStore && ($storeId = (int)Mage::app()->getRequest()->getParam('store'))) {
			return array($storeId);
		}
		
		$request = Mage::app()->getRequest();
		
		if ($request->getControllerName() === 'cms_page' && $request->getActionName() === 'save') {
			$page = Mage::getModel('cms/page')->load((int)$request->getParam('page_id'));
			
			if ($page->getId()) {
				if ($storeIds = $page->getStoreId()) {
					return array((int)array_shift($storeIds));
				}
			}
		}
		
		$select = $this->_getReadAdapter()
			->select()
			->from($this->_getTable('core/store'), 'store_id')
			->where('store_id>?', 0);
					
		return $this->_getReadAdapter()->fetchCol($select);
	}

	/**
	 * Retrieve a single store ID
	 *
	 * @return int
	 */
	public function getSingleStoreId()
	{
		$storeIds = $this->getAssociationStoreIds();
		
		if (is_array($storeIds)) {
			return (int)array_shift($storeIds);
		}
		
		return (int)$storeIds;
	}

	/**
	 * Convert an array of WordPress category ID's to
	 * an array of post ID's
	 *
	 * @param array $categoryIds
	 * @return array
	 */
	protected function _convertWpCategoryIds(array $categoryIds)
	{
		if (count($categoryIds) === 0) {
			return array();
		}

		$select = $this->_getReadAdapter()
			->select()
			->from(array('term' => $this->_getTable('wordpress/term')), '')
			->where('term.term_id IN (?)', $categoryIds);
			
		$select->join(
			array('tax' => $this->_getTable('wordpress/term_taxonomy')),
			"`tax`.`term_id` = `term`.`term_id` AND `tax`.`taxonomy`= 'category'",
			''
		);
		
		$select->join(
			array('rel' => $this->_getTable('wordpress/term_relationship')),
			"`rel`.`term_taxonomy_id` = `tax`.`term_taxonomy_id`",
			'object_id'
		);
		
		return Mage::helper('wordpress/app')->getDbConnection()->fetchCol($select);
	}

	/**
	 * Retrieve the read DB adapter
	 *
	 * @return
	 */	
	protected function _getReadAdapter()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_read');
	}
	
	/**
	 * Retrieve the write DB adapter
	 *
	 * @return
	 */
	protected function _getWriteAdapter()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
	
	/**
	 * Retrieve a table name by entity
	 *
	 * @param string $entity
	 * @return string
	 */	
	protected function _getTable($entity)
	{
		return Mage::getSingleton('core/resource')->getTableName($entity);	
	}
	
	/**
	 * Ensure association tables are installed
	 *
	 * @return $this
	 */
	public function checkForTables()
	{
		$resource = Mage::getSingleton('core/resource');
		$write = $resource->getConnection('core_write');
		$read = $resource->getConnection('core_read');
		
		$associationTypeTable = $resource->getTableName('wordpress/association_type');
		$associationTable = $resource->getTableName('wordpress/association');
		
		$tables = array(
			$associationTypeTable => "CREATE TABLE IF NOT EXISTS %s (
				`type_id` int(11) unsigned NOT NULL auto_increment,
				`object` varchar(16) NOT NULL default '',
				`wordpress_object` varchar(16) NOT NULL default '',
				PRIMARY KEY(type_id)
			)  ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			$associationTable => "CREATE TABLE IF NOT EXISTS %s (
				`assoc_id` int(11) unsigned NOT NULL auto_increment,
				`type_id` int(3) unsigned NOT NULL default 0,
				`object_id` int(11) unsigned NOT NULL default 0,
				`wordpress_object_id` int(11) unsigned NOT NULL default 0,
				`position` int(4) unsigned NOT NULL default 4444,
				`store_id` smallint(5) unsigned NOT NULL default 0,
				PRIMARY KEY (`assoc_id`),
				CONSTRAINT `FK_WP_ASSOC_TYPE_ID_WP_ASSOC_TYPE` FOREIGN KEY (`type_id`) REFERENCES `{$associationTypeTable}` (`type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
				KEY `FK_WP_ASSOC_TYPE_ID_WP_ASSOC_TYPE` (`type_id`),
				KEY `FK_STORE_ID_WP_ASSOC` (`store_id`),
				CONSTRAINT `FK_STORE_ID_WP_ASSOC` FOREIGN KEY (`store_id`) REFERENCES `{$resource->getTableName('core_store')}` (`store_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	
		$missingTables = false;

		foreach($tables as $table => $createSql) {
			try {
				$read->fetchOne($read->select()->from($table)->limit(1));
			}
			catch (Exception $e) {
				$missingTables = true;
				$write->query(sprintf($createSql, $table));
			}
		}
	
		if ($missingTables) {
			$types = array(
				1 => array('product', 'post'),
				2 => array('product', 'category'),
				3 => array('category', 'post'),
				4 => array('category', 'category'),
				5 => array('cms_page', 'post'),
				6 => array('cms_page', 'category'),
			);
				
			$select = $read->select()
				->from($associationTypeTable, 'type_id')
				->limit(1);
				
			if (!$read->fetchOne($select)) {
				foreach($types as $typeId => $data) {
					$write->query(sprintf("INSERT INTO %s VALUES (%d, '%s', '%s');\n", $associationTypeTable, $typeId, $data[0], $data[1]));
				}
			}
		}			

		return $this;
	}
}
