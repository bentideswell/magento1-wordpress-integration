<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Addon_Yarpp_Block_Sidebar_Widget extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Determine whether Yarpp is enabled
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return Mage::helper('wordpress')->isPluginEnabled('yet-another-related-posts-plugin/yarpp.php');
	}
	
	/**
	 * Load the options for the widget/block and set the posts
	 *
	 * @return $this
	 */
	protected function _beforeToHtml()
	{
		if (!$this->isEnabled()) {
			return $this;
		}
		
		$options = unserialize($this->helper('wordpress')->getWpOption('yarpp'));
		
		if (is_array($options)) {
			if (isset($options['template'])) {
				$options['view'] = $options['template'];
				unset($options['template']);
			}

			$this->addData($options);
		}

		parent::_beforeToHtml();

		$this->setPosts($this->_getPostCollection());

		return $this;
	}
	
	/**
	 * Control the number of posts displayed
	 *
	 * @param int $count
	 * @return $this
	 */
	public function setPostCount($count)
	{
		return $this->setNumber($count);
	}
	
	/**
	 * Retrieve the current post object
	 *
	 * @return Fishpig_Wordpress_Model_Post|false
	 */
	public function getPost()
	{
		return $this->hasPost() ? $this->_getData('post') : Mage::registry('wordpress_post');
	}
	
	/**
	 * Adds on cateogry/author ID filters
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	protected function _getPostCollection()
	{
		if ($this->getPost()) {
			$collection = $this->getRelatedPostCollection($this->getPost())
				->setCurPage(1);
				
			if ($this->getNumber()) {
				$collection->setPageSize($this->getNumber());
			}
			
			return $collection->load();
		}
		
		return array();
	}
	
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->__('Related Posts');
	}
	
	/**
	 * Determine whether the thumbnail is 
	 *
	 * @return bool
	 */
	public function isThumbnailView()
	{
		return $this->_getData('view') === 'thumbnails';
	}
	
	/**
	 * Retrieve the thumbnail image for a post
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return string
	 */
	public function getThumbnailImage(Fishpig_Wordpress_Model_Post $post)
	{
		if (($image = $post->getFeaturedImage()) !== false) {
			return $image->getAvailableImage();
		}
		
		return $this->_getData('thumbnails_default');	
	}

	/**
	 * Determine whether to show a post excerpt
	 *
	 * @return bool
	 */
	public function canShowExcerpt()
	{
		return $this->_getData('show_excerpt') == '1';
	}
	
	/**
	 * Retrieve the post excerpt
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return string
	 */
	public function getPostExcerpt(Fishpig_Wordpress_Model_Post $post)
	{
		if ($excerpt = trim(strip_tags($post->getPostExcerpt()))) {
			$words = explode(' ', $excerpt);
			
			if (count($words) > $this->getExcerptLength()) {
				$words = array_slice($words, 0, $this->getExcerptLength());
			}
			
			return trim(implode(' ', $words), '.,!:-?"\'£$%') . '...';
		}
		
		return '';
	}
	
	/**
	 * Retrieve the HTML content that goes before the related post block
	 *
	 * @return string
	 */
	public function getBeforeBlockHtml()
	{
		return $this->_getData('before_related');
	}

	/**
	 * Retrieve the HTML content that goes after the related post block
	 *
	 * @return string
	 */	
	public function getAfterBlockHtml()
	{
		return $this->_getData('after_related');
	}
	
	/**
	 * Retrieve the HTML content that goes before a related entry
	 *
	 * @return string
	 */
	public function getBeforeEntryHtml()
	{
		return $this->_getData('before_title');
	}

	/**
	 * Retrieve the HTML content that goes after a related entry
	 *
	 * @return string
	 */
	
	public function getAfterEntryHtml()
	{
		return $this->_getData('after_title');
	}

	/**
	 * Retrieve the HTML content that goes before a post excerpt
	 *
	 * @return string
	 */
	public function getBeforeExcerptHtml()
	{
		return $this->_getData('before_post');
	}

	/**
	 * Retrieve the HTML content that goes after a post excerpt
	 *
	 * @return string
	 */	
	public function getAfterExcerptHtml()
	{
		return $this->_getData('after_post');
	}

	/**
	 * Retrieve a collection of related posts
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	*/
	public function getRelatedPostCollection(Fishpig_Wordpress_Model_Post $post)
	{
		return Mage::getResourceModel('wordpress/post_collection')
			->addIsViewableFilter()
			->addFieldToFilter('ID', array('in' => $this->getRelatedPostIds($post)));
	}
	
	/**
	 * Retrieve an array of related post ID's
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return array|false
	*/
	public function getRelatedPostIds(Fishpig_Wordpress_Model_Post $post)
	{
		if (!$this->isEnabled()) {
			return array();
		}

		$helper = Mage::helper('wordpress/app');

		$select = $helper->getDbConnection()
			->select()
			->from($helper->getTableName('yarpp_related_cache'), 'ID')
			->where('reference_ID=?', $post->getId())
			->where('score > ?', 0)
			->order($this->getOrder() ? $this->getOrder() : 'score DESC')
			->limit($this->getLimit() ? $this->getLimit() : 5);

		try {
			return $helper->getDbConnection()->fetchCol($select);
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e->getMessage());
		}
		
		return array();
	}		
}
