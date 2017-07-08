<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Block_Adminhtml_Associations_Abstract extends Mage_Adminhtml_Block_Widget_Grid
implements Mage_Adminhtml_Block_Widget_Tab_Interface 
{
	/**
	 * Retrieve the association type for the particular class
	 *
	 * @return string
	 */
	abstract public function getAssociationType();

	/**
	 * Setup the grid
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setId($this->_getMagentoEntity() . $this->_getWpEntity());
		$this->setDefaultSort($this->_getDefaultSort());
		$this->setDefaultDir($this->_getDefaultDir());
		$this->setSaveParametersInSession(false);
		$this->setUseAjax(true);
		
		if (is_object($this->_getObject())) {
			$this->setDefaultFilter(array('is_associated' => 1));
		}
	}
        
	/**
	 * Returns an array of all of the object's associated WP ID's
	 *
	 * @return array
	 */
	public function getSelectedWpItems()
	{
		return array_keys($this->getSelectedWpItemPositions());
	}
	
	/**
	 * Retrieves an array of associated WP ID's and their position
	 *
	 * @return array
	 */
	public function getSelectedWpItemPositions()
	{
		if ($this->_getObject()) {
			$wpObjectIds = Mage::helper('wordpress/associations')->getAssociations($this->getAssociationType(), $this->_getObject()->getId(), $this->getStoreId());

			if ($wpObjectIds !== false) {
				foreach($wpObjectIds as $wpObjectId => $position) {
					$wpObjectIds[$wpObjectId] = array('associated_position' => $position);
				}

				return $wpObjectIds;
			}
		}

		return array();
	}
    
    /**
     * Add in the position field to the grid
     *
     */
    protected function _prepareColumns()
    {
		$this->addColumn('is_associated', array(
			'header_css_class'  => 'a-center',
			'type' => 'checkbox',
			'name'	=> 'is_associated',
			'align' => 'center',
			'index' => $this->_getWpPrimaryKey(),
			'values' => array_values($this->getSelectedWpItems()),
		));
		
    	if ($this->_getWpEntity() === 'post') {
			$this->addColumn('post_title', array(
				'header'=> 'Post Title',
				'index' => 'post_title',
			));
			
			$postTypes = array_keys(Mage::helper('wordpress/app')->getPostTypes());
						
			$this->addColumn('post_type', array(
				'header'=> 'Type',
				'index' => 'post_type',
				'type' => 'options',
				'options' => array_combine($postTypes, $postTypes),
			));
			
			$this->addColumn('post_date', array(
				'header'=> 'Post Date',
				'index' => 'post_date',
				'type' => 'date',
				'format' => 'd MMMM YYYY',
			));
    	}
    	else if ($this->_getWpEntity() === 'category') {		
			$this->addColumn('name', array(
				'header'=> 'Name',
				'index' => 'name',
			));

			$this->addColumn('slug', array(
				'header'=> 'Slug',
				'index' => 'slug',
			));
			
			$taxonomies = array_keys(Mage::helper('wordpress/app')->getTaxonomies());
						
			$this->addColumn('taxonomy', array(
				'header'=> 'Type',
				'index' => 'taxonomy',
				'type' => 'options',
				'options' => array_combine($taxonomies, $taxonomies),
			));
    	}
    	
		$this->addColumn('associated_position', array(
			'header'				=> Mage::helper('catalog')->__('Position'),
			'name'				=> 'associated_position',
			'type'				=> 'number',
			'validate_class'	=> 'validate-number',
			'index'				=> 'associated_position',
			'width'				=> 100,
			'editable'			=> true,
			'filterable'			=> false,
			'sortable'			=> false,
		));
		
		return parent::_prepareColumns();
	}

    protected function _prepareCollection()
    {
	    if (!$this->getCollection()) {
			if ($this->_getWpEntity() === 'post') {
				$collection = Mage::getResourceModel('wordpress/post_collection')
					->addPostTypeFilter(array_keys(Mage::helper('wordpress/app')->getPostTypes()))
#					->removePermalinkFromSelect()
					->addIsViewableFilter();
			}
			else if ($this->_getWpEntity() === 'category') {
				$collection = Mage::getResourceModel('wordpress/term_collection')->addTaxonomyFilter(
					array_keys(Mage::helper('wordpress/app')->getTaxonomies())
				);
			}
			else {
				return false;
			}
			
	#		Mage::dispatchEvent('wordpress_association_' . $this->_getWpEntity() . '_collection_load_before', array('collection' => $collection, 'grid' => $this));
			
			Mage::helper('wordpress/associations')->addRelatedPositionToSelect($collection, $this->getAssociationType(), $this->_getObject()->getId(), $this->getStoreId());
	
			$this->setCollection($collection);
		}
		
		try {
			 return parent::_prepareCollection();
		}
		catch (Exception $e) {
			echo sprintf('<div><ul class="messages"><li class="error-msg"><ul><li><span>%s</span></li></ul></li></ul></div>', $e->getMessage());
			echo '<p>Your WordPress database user does not have permission to access the association tables in the Magento database. To fix this, either grant SELECT permission for your WordPress database user to the Magento tables that start with wordpress_ or merge your WordPress and Magento databases.</p>';
			echo '<p>If you are having trouble doing this or just want it handling quickly for you, FishPig offer a <a href="http://fishpig.co.uk/magento/wordpress-integration/services/merge-databases/?ref=errassoc" target="_blank">WordPress and Magento database merging</a> service.</p>';
			exit;
		}
	}
	
	/**
	 * Add a custom filter for the in_product column
	 *
	 */
	protected function _addColumnFilterToCollection($column)
	{
		if ($column->getId() === 'is_associated') {
			$ids = $this->getSelectedWpItems();
			
			if (empty($ids)) {
				$ids = array(0);
			}
			
			$op = $column->getFilter()->getValue() ? 'in' : 'nin';

			$this->getCollection()->addFieldToFilter("main_table." . $this->_getWpPrimaryKey(), array($op => $ids));
		}
		else {
			parent::_addColumnFilterToCollection($column);
		}
		
		return $this;
	}

	/**
	 * Displays the tab if integration is valid
	 *
	 * @return true
	 */
    public function canShowTab()
    {
		return $this->integrationIsEnabled() && (is_object($this->_getObject()) && $this->_getObject()->getId());
    }
    
	/**
	 * Determine whether integration is enabled
	 *
	 * @return bool
	 */
	public function integrationIsEnabled()
	{
		return Mage::helper('wordpress/app')->getDbConnection() !== false;
	}

    /**
     * Stops the tab being hidden
     *
     * @return bool
     */
    public function isHidden()
    {
    	return false;
    }
    
	/**
	 * Retrieve the class name of the tab
	 *
	 * return string
	 */
	public function getTabClass()
	{
		return 'ajax';
	}

	/**
	 * Determine whether to generate content on load or via AJAX
	 *
	 * @return bool
	 */
	public function getSkipGenerateContent()
	{
		return true;
	}

	/**
	 * Retrieve the default sort
	 *
	 * @return string
	 */
	protected function _getDefaultSort()
	{
		return 'assoc.position';
	}
	
	/**
	 * Retrieve the default sort direction
	 *
	 * @return string
	 */
	protected function _getDefaultDir()
	{
		return 'asc';
	}
	
	/**
	 * Retrieve the parameters for the URL
	 *
	 * @return array
	 */
	protected function _getUrlParams()
	{
		$params = array(
			'associations_type' => $this->getAssociationType(),
		);
		
		if ($this->_getObject()) {
			$params['id'] = $this->_getObject()->getId();
		}
		
		if ($store = Mage::app()->getRequest()->getParam('store', false)) {
			$params['store'] = $store;
		}
		
		return $params;
	}
	
	/**
	 * Retrieve the URL used to access the grid (AJAX)
	 *
	 * @return string
	 */
	public function getCurrentUrl($params = array())
	{
		return $this->getUrl('adminhtml/wordpress_associations/gridRefresh', array('_query' => $this->_getUrlParams()));
	}
	
	/**
	 * Retrieve the URL used to load the tab content
	 *
	 * @return string
	 */
	public function getTabUrl()
	{
		return $this->getUrl('adminhtml/wordpress_associations/grid', array('_query' => $this->_getUrlParams()));
	}
	
	/**
	 * Retrieve the Magento entity type
	 *
	 * @return string
	 */
	protected function _getMagentoEntity()
	{
		return substr($this->getAssociationType(), 0, strpos($this->getAssociationType(), '/'));
	}

	/**
	 * Retrieve the WordPress entity type
	 *
	 * @return string
	 */	
	protected function _getWpEntity()
	{
		return substr($this->getAssociationType(), strpos($this->getAssociationType(), '/')+1);
	}
	
	/**
	 * Retrieve the WordPress entity primary key
	 *
	 * @return string
	 */
	protected function _getWpPrimaryKey()
	{
		if ($this->_getWpEntity() === 'category') {
			return 'term_id';
		}

		return 'ID';
	}
	
	/**
	 * Returns the current product model
	 *
	 * @return Mage_Catalog_Model_Product
	 */
	protected function _getObject()
	{	
		return Mage::registry($this->_getMagentoEntity());
	}
	
	/**
	 * Retrieve the label used for the tab relating to this block
	 *
	 * @return string
	 */
    public function getTabLabel()
    {
    	if ($this->_getWpEntity() === 'post') {
	    	return $this->__('Associated Blog Posts');
	    }
	    else if ($this->_getWpEntity() === 'category') {
	    	return $this->__('Associated Blog Categories');
	    }
	    
	    return '';
	}
        
    /**
     * Retrieve the title used by this tab
     *
     * @return string
     */
    public function getTabTitle()
    {
    	return $this->getTabLabel();
    }
    
    /**
     * Retrieve the store ID set in the controller
     *
     * @return int
     */
    public function getStoreId()
    {
    	return Mage::app()->getFrontController()->getAction()->getStoreId();
    }
    
    public function getMultipleRows($item)
    {
	    return false;
    }
}
