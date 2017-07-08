<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Block_Post_Abstract extends Fishpig_Wordpress_Block_Abstract
{
	/**
	 * Retrieve the current post object
	 *
	 * @return null|Fishpig_Wordpress_Model_Post
	 */
	public function getPost()
	{
		return $this->hasPost() ? $this->_getData('post') : Mage::registry('wordpress_post');
	}

	/**
	 * Legacy function so that old templates continue to work
	 *
	 * @return Fishpig_Wordpress_Model_Post
	 */
	public function getPage()
	{
		return $this->getPost();
	}
	
	/**
	 * Returns the ID of the currently loaded post
	 *
	 * @return int|false
	 */
	public function getPostId()
	{
		return $this->getPost() ? $this->getPost()->getId() : false;
	}
	
	/**
	 * Returns true if comments are enabled for this post
	 *
	 * @return bool
	 */
	protected function canComment()
	{
		return $this->getPost() 
			&& $this->getPost()->getCommentStatus() === 'open';
	}
	
	/**
	 * Determine whether previous/next links are enabled in the config
	 *
	 * @return bool
	 */
	public function canDisplayPreviousNextLinks()
	{
		return (bool)$this->_getData('display_previous_next_links');
	}
	
	/**
	 * Retrieve the HTML for the password protect form
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return string
	 */
	public function getPasswordProtectHtml($post = null)
	{
		if (is_null($post)) {
			$post = $this->getPost();
		}

		return $this->getLayout()
			->createBlock('wordpress/template')
			->setTemplate('wordpress/protected.phtml')
			->setEntityType('post')
			->setPost($post)
			->toHtml();
	}
	
	/**
	 * Determine whether to display the full post content or the excerpt
	 *
	 * @return bool
	 */
	public function displayExcerptInFeed()
	{
		return Mage::helper('wordpress')->getWpOption('rss_use_excerpt') == '1';
	}
	
	/**
	 * If post view, setup the post with child blocks
	 *
	 * @return $this
	 */
	protected function _beforeToHtml()
	{
		if ($this->getPost() && $this->_getBlockForPostPrepare() !== false) {
			$this->preparePost($this->getPost());
		}
		
		return parent::_beforeToHtml();
	}
	
	/**
	 * Set the post as the current post in all child blocks
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return $this
	 */
	public function preparePost(Fishpig_Wordpress_Model_Post $post)
	{	
		if (($rootBlock = $this->_getBlockForPostPrepare()) !== false) {
			foreach($rootBlock->getChild('') as $alias => $block) {
				$block->setPost($post);
			
				foreach($block->getChild('') as $calias => $cblock) {
					$cblock->setPost($post);
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Retrieve the block used to prepare the post
	 * This should be the root post block
	 *
	 * @return Fishpig_Wordpress_Block_Post_Abstract
	 */
	protected function _getBlockForPostPrepare()
	{
		return $this;
	}
	
	/**
	 * Retrieve the after_post_content HTML
	 *
	 * @return string
	 */
	public function getAfterPostContentHtml()
	{
		return $this->_getChildTextList('after_post_content');
	}
	
	/**
	 * Retrieve the before_post_content HTML
	 *
	 * @return string
	 */
	public function getBeforePostContentHtml()
	{
		return $this->_getChildTextList('before_post_content');
	}
	
	/**
	 * Validate and retrieve a child core/text_list HTML
	 *
	 * @param string $name
	 * @return string
	 */
	protected function _getChildTextList($name)
	{
		if (($block = $this->_getBlockForPostPrepare()) !== false) {
			if (($child = $block->getChild($name)) !== false) {
				if ($child->getChild('')) {
					return $child->toHtml();
				}
			}
		}
		
		return '';
	}
	
	/**
	 * Get the Meta block
	 *
	 * @return Fishpig_Wordpress_Block_Post_Meta
	 */
	public function getMetaBlock()
	{
		if (!$this->hasMetaBlock()) {
			$this->setMetaBlock(
				$this->getLayout()->createBlock('wordpress/post_meta')
					->setTemplate('wordpress/post/meta.phtml')
			);
		}
		
		return $this->_getData('meta_block');
	}
}
