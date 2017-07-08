<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Adminhtml_Wordpress_AssociationsController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Determine ACL permissions
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return true;
	}
	
	/**
	 * Display the initial grid
	 *
	 */
	public function gridAction()
	{
		$this->_performAssociationAction();
	}
	
	/**
	 * Display the grid after a refresh via AJAX
	 *
	 */
	public function gridRefreshAction()
	{
		$this->_performAssociationAction('_grid');
	}
	
	/**
	 * This method actually handles loading the layout
	 * and displaying the grid
	 * $handlePostfix allows you to specify a postfix for the layout handle
	 *
	 * @param string $handlePostfix = ''
	 * @return void
	 */
	protected function _performAssociationAction($handlePostfix = '')
	{
		try {
			if (!$this->_isSingleStoreMode() && $this->getStoreId() === false) {
				$this->getResponse()
					->setBody('<p style="font-size: 18px; margin-top: 40px; text-align: center;">Please select a store using the Store View selector.</p>');
					
				return false;
			}
			if ($this->_initObject() === false) {
				return $this->_forward('noRoute');
			}

/*
			$storeIds = $this->_initObject()->getStoreId();
		
			if (count($storeIds) === 1) {
				$appEmulation = Mage::getSingleton('core/app_emulation');
				$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation(array_shift($storeIds));
			}
*/

			if (Mage::helper('wordpress/app')->getDbConnection() === false) {
				return $this->_forward('noWordPressDatabase');;
			}
			
#			$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
	
			$handle = 'adminhtml_wordpress_association_' . $this->_getMagentoEntity() . '_' . $this->_getWpEntity() . $handlePostfix;
			
			$this->loadLayout($handle);
	
			$this->getLayout()->getUpdate()
				->removeHandle('adminhtml_wordpress_associations_grid')
				->removeHandle('STORE_admin')
				->removeHandle('THEME_adminhtml_default_default');
			
			$this->renderLayout();
		}
		catch (Exception $e) {
			$this->getResponse()->setBody(
				sprintf('<h1>%s</h1><pre>%s</pre>', $e->getMessage(), $e->getTraceAsString())
			);
		}
	}

	/**
	 * Retrieve the current association type
	 *
	 * return false|string
	 */
	public function getAssociationType()
	{
		return $this->getRequest()->getParam('associations_type', false);
	}
	
	/**
	 * Retrieve the Magento entity type
	 *
	 * @return string
	 */
	protected function _getMagentoEntity()
	{
		if (($type = $this->getAssociationType()) !== false) {
			return substr($type, 0, strpos($type, '/'));
		}
		
		return false;
	}

	/**
	 * Retrieve the WordPress entity type
	 *
	 * @return string
	 */	
	protected function _getWpEntity()
	{
		if (($type = $this->getAssociationType()) !== false) {
			return substr($type, strpos($type, '/')+1);
		}
		
		return false;
	}
	
	/**
	 * Determine whether only 1 store exists
	 *
	 * @return bool
	 */
	protected function _isSingleStoreMode()
	{
		return Mage::app()->isSingleStoreMode() || Mage::helper('wordpress')->forceSingleStore();
	}
	
	/**
	 * Retrieve the current store ID
	 *
	 * @return int
	 */
	public function getStoreId()
	{
		if ($this->_getMagentoEntity() === 'cms_page') {
			return 0;
		}

		if (!$this->_isSingleStoreMode()) {
			return $this->getRequest()->getParam('store', false);
		}
	
		return Mage::helper('wordpress')->getDefaultStore()->getId();
	}
	
	/**
	 * Initialize the Magento object
	 *
	 * @return Mage_Core_Model_Abstract|false
	 */
	protected function _initObject()
	{
		if ($this->_getMagentoEntity() === 'product') {
			return $this->_initProduct();
		}
		else if ($this->_getMagentoEntity() === 'category') {
			return $this->_initCategory();
		}
		else if ($this->_getMagentoEntity() === 'cms_page') {
			return $this->_initCmsPage();
		}
		
		return false;
	}	
	
	/**
	 * Initialise the product model
	 * This should only be called via AJAX actions
	 *
	 * @return false|Mage_Catalog_Model_Product
	 */
	protected function _initProduct()
	{
		if (!Mage::registry('product')) {
			if ($productId = $this->getRequest()->getParam('id')) {
				$product = Mage::getModel('catalog/product')->load($productId);
				
				if ($product->getId()) {
					Mage::register('product', $product);
					return $product;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Initialise the category model
	 * This should only be called via AJAX actions
	 *
	 * @return false|Mage_Catalog_Model_Category
	 */
	protected function _initCategory()
	{
		if (!Mage::registry('category')) {
			if ($categoryId = $this->getRequest()->getParam('id')) {
				$category = Mage::getModel('catalog/category')->load($categoryId);
				
				if ($category->getId()) {
					Mage::register('category', $category);
					return $category;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Initialise the category model
	 * This should only be called via AJAX actions
	 *
	 * @return false|Mage_Catalog_Model_Category
	 */
	protected function _initCmsPage()
	{
		if (!Mage::registry('cms_page')) {
			if ($pageId = $this->getRequest()->getParam('id')) {
				$page = Mage::getModel('cms/page')->load($pageId);
				
				if ($page->getId()) {
					Mage::register('cms_page', $page);
					return $page;
				}
			}
		}
		
		return false;
	}
}
