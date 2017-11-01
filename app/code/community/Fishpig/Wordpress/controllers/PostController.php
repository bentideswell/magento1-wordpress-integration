<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
class Fishpig_Wordpress_PostController extends Fishpig_Wordpress_Controller_Abstract
{
	protected $_isPreview = null;
	
	/**
	 * Used to do things en-masse
	 * eg. include canonical URL
	 *
	 * @return false|Mage_Core_Model_Abstract
	 */
	public function getEntityObject()
	{
		return $this->_initPost();
	}

	protected function _isPreview()
	{
		if ($this->_isPreview === null) {
			$this->_isPreview = (int)$this->getRequest()->getParam('preview_id') > 0
				|| $this->getRequest()->getParam('preview', false) !== false
				|| $this->getRequest()->getActionName() === 'preview';
		}
		
		return $this->_isPreview;
	}
	
	/**
	 * Display appropriate message for posted comment
	 *
	 * @return $this
	 */
	public function preDispatch()
	{
		parent::preDispatch();
		
		$this->_handlePostedComment();
		
		$post = $this->getEntityObject();

		if ($post->isBlogListingPage()) {
			return $this->_forward('index', 'index', 'wordpress');
		}

		if ($post->getTypeInstance()->getCustomRoute()) {
			list($module, $controller, $action) = explode('/', $post->getTypeInstance()->getCustomRoute());
			
			return $this->_forward($action, $controller, $module);
		}

		return $this;
	}

	public function previewAction()
	{
		return $this->_forward('view');
	}

	/**
	 * Display the post view page
	 *
	 */
	public function viewAction()
	{
		$post = $this->getEntityObject();

		$layoutHandles = array(
			'wordpress_post_view',
			'wordpress_' . $post->getPostType() . '_view',
		);

		if ($post->getPostType() == 'revision' && $post->getParentPost()) {
			$layoutHandles[] = 'wordpress_' . $post->getParentPost()->getPostType() . '_view';
		}
                
		$isHomepage = (bool)$this->getRequest()->getParam('is_homepage');
		
		if ($post->isHomepagePage() && !$isHomepage) {
			if (Mage::getUrl('', array('_current' => true, '_use_rewrite' => true)) !== Mage::helper('wordpress')->getUrl()) {
				return $this->_redirectUrl(Mage::helper('wordpress')->getUrl());
			}
		}
		
		if ($post->getTypeInstance()->isHierarchical()) {
			$buffer = $post->getParentPost();
	
			while ($buffer) {
				$layoutHandles[] = 'wordpress_' . $post->getPostType() . '_view_parent_' . $buffer->getId();
				
				// Legacy
				if ($post->isType('page')) {
					$layoutHandles[] = 'wordpress_' . $post->getPostType() . '_parent_' . $buffer->getId();
				}

				$buffer = $buffer->getParentPost();
			}
		}

		// Add the layout handle for the post type and ID
		$layoutHandles[] = 'wordpress_' . $post->getPostType() . '_view_' . $post->getId();
		
		if ($post->isHomepagePage() && $isHomepage) {
			$layoutHandles[] = 'wordpress_frontpage';
		}

		$this->_addCustomLayoutHandles($layoutHandles);
		$this->_initLayout();
		$this->_title(strip_tags($post->getPostTitle()));

		if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
			$headBlock->addItem(
				'link_rel', 
				$post->getCommentFeedUrl(), 
				sprintf('rel="alternate" type="application/rss+xml" title="%s &raquo; %s Comments Feed"', 
					Mage::helper('wordpress')->getWpOption('blogname'), 
					$post->getPostTitle()
				)
			);

			if (Mage::helper('wordpress')->getWpOption('default_ping_status') === 'open' && $post->getPingStatus() == 'open') {
				$headBlock->addItem('link_rel', Mage::helper('wordpress')->getBaseUrl() . 'xmlrpc.php', 'rel="pingback"');				
			}
		}

		if ($post->getTypeInstance()->hasArchive()) {
			$this->addCrumb($post->getPostType() . '_archive', array('label' => $post->getTypeInstance()->getName(), 'link' => $post->getTypeInstance()->getArchiveUrl()));
		}
		
		if ($isHomepage) {
			$post->setCanonicalUrl(Mage::helper('wordpress')->getUrl());

			if (Mage::helper('wordpress')->getBlogRoute() === '') {
				$this->_crumbs = array();
			}
			else {
				array_pop($this->_crumbs);
			}
		}
		else if ($post->getTypeInstance()->isHierarchical()) {
			$posts = array();
			$buffer = $post;
	
			while ($buffer) {
				$this->_title(strip_tags($buffer->getPostTitle()));
				$posts[] = $buffer;
				$buffer = $buffer->getParentPost();
			}

			$posts = array_reverse($posts);
			
			// Remove current post from end array
			array_pop($posts);
			
			foreach($posts as $buffer) {
				$this->addCrumb('post_' . $buffer->getId(), array('label' => $buffer->getPostTitle(), 'link' => $buffer->getUrl()));
			}
		}
		else if ($taxonomy = $post->getTypeInstance()->getAnySupportedTaxonomy('category')) {
			if ($term = $post->getParentTerm($taxonomy->getTaxonomyType())) {
				$terms = array();
	
				while($term) {
					array_unshift($terms, $term);
					$term = $term->getParentTerm();
				}
				
				foreach($terms as $term) {
					$this->addCrumb('post_' . $term->getTaxonomyType() . '_' . $term->getId(), array('label' => $term->getName(), 'link' => $term->getUrl()));
				}
			}
		}
		
		if (!$isHomepage) {
			$this->addCrumb('post', array('label' => $post->getPostTitle()));
		}
		
		// Revisions don't have the template meta, grab it from parent
		if ($post->getPostType() === 'revision' && ($parent = $post->getParentPost())) {
			$template = $parent->getMetaValue('_wp_page_template');
		}
		else {
			$template = $post->getMetaValue('_wp_page_template');
		}
		
		if ($template) {
			$template = str_replace(array('template-', '.php'), '', $template);
			
			if (in_array($template, array('1column', '2columns-left', '2columns-right', '3columns'))) {
				if ($root = $this->getLayout()->getBlock('root')) {
					$root->setTemplate('page/' . $template . '.phtml');
				}
			}
			else if (strpos($template, 'full-width') !== false) {
				// Legacy
				if ($root = $this->getLayout()->getBlock('root')) {
					$root->setTemplate('page/1column.phtml');
				}
			}
		}

		if ($rootBlock = $this->getLayout()->getBlock('root')) {
			$rootBlock->addBodyClass('wordpress-' . $post->getPostType() . '-' . $post->getId());
		}

		$this->renderLayout();
	}

	/**
	 * Display the appropriate message for a posted comment
	 *
	 * @return $this
	 */
	protected function _handlePostedComment()
	{
		$commentId = $this->getRequest()->getParam('comment');
		
		if ($commentId && $this->getRequest()->getActionName() === 'view') {
			$comment = Mage::getModel('wordpress/post_comment')->load($commentId);
			
			if ($comment->getId() && $comment->getPost()->getId() === $this->getEntityObject()->getId()) {
				if ($comment->isApproved()) {
					header('Location: ' . $comment->getUrl());
					exit;
				}
				else {
					Mage::getSingleton('core/session')->addSuccess($this->__('Your comment is awaiting moderation.'));	
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Initialise the post model
	 * Provides redirects for Guid links when using permalinks
	 *
	 * @return false|Fishpig_Wordpress_Model_Post
	 */
	protected function _initPost()
	{
		if (($post = Mage::registry('wordpress_post')) !== null) {
			$previewId = $this->getRequest()->getParam('preview_id');

			if ($previewId === $post->getId()) {
				$posts = Mage::getResourceModel('wordpress/post_collection')
					->addFieldToFilter('post_parent', $post->getId())
					->addPostTypeFilter('revision')
					->setPageSize(1)
					->setOrder('post_modified', 'desc')
					->load();
				
				if (count($posts) > 0) {
					$post = $posts->getFirstItem();
					
					Mage::unregister('wordpress_post');
					Mage::register('wordpress_post', $post);
					
					return $post;	
				}
			}
			
			return $post;
		}

		$isPreview = $this->_isPreview();

		if ($postId = $this->getRequest()->getParam('p')) {
			$post = Mage::getModel('wordpress/post')->load($postId);

			if ($post->getId()) {
				if ($isPreview || $post->getTypeInstance() && $post->getTypeInstance()->useGuidLinks()) {
					Mage::register('wordpress_post', $post);

					return $post;
				}

				if ($post->canBeViewed()) {
					$this->_redirectUrl($post->getUrl());
					$this->getResponse()->sendHeaders();
					exit;
				}
			}
		}
		else if (($pageId = $this->getRequest()->getParam('page_id')) && $isPreview) {
			$post = Mage::getModel('wordpress/post')
				->setPostType('page')
				->load($pageId);

			if ($post->getId()) {
				Mage::register('wordpress_post', $post);

				return $post;
			}
		}
		else if ($postId = $this->getRequest()->getParam('id')) {
			$post = Mage::getModel('wordpress/post')
				->setPostType($this->getRequest()->getParam('post_type', '*'))
				->load($postId);
			
			if ($post->getId() && ($post->canBeViewed() || $isPreview)) {
				Mage::register('wordpress_post', $post);
				
				return $post;
			}
		}

		return false;
	}

	/**
	 * Display the comments feed
	 *
	 * @return void
	 */	
	public function feedAction()
	{
		return $this->commentsFeedAction();
	}
}
