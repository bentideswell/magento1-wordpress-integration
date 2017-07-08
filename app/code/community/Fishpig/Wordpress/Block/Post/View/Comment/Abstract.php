<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Block_Post_View_Comment_Abstract extends Fishpig_Wordpress_Block_Post_Abstract
{
	/**
	 * Returns a collection of comments for the current post
	 *
	 * @return Fishpig_Wordpress_Model_Resource_Post_Comments_Collection
	 */
	public function getComments()
	{
		if (!$this->hasComments()) {
			if ($this->getCommentCount() > 0 && ($post = $this->getPost()) !== false) {
				$this->setComments($post->getResource()->getPostComments($post));

				if (($pager = $this->getChild('pager')) !== false) {
					$this->_getData('comments')->setPageSize($pager->getLimit());
				}
			}
			else {
				$this->setComments(new Varien_Data_Collection());
			}
		}
		
		return $this->_getData('comments');
	}

	/**
	 * Retrieve the amount of comments for the current post
	 *
	 * @return int
	 */
	public function getCommentCount()
	{
		return $this->getPost() ? $this->getPost()->getCommentCount() : 0;
	}
	
	/**
	 * Determine whether comments are enabled
	 *
	 * @return bool
	 */
	public function isCommentsEnabled()
	{
		return $this->getPost() && $this->getPost()->getCommentStatus() !== 'closed';
	}
		
	/**
	 * Get the comment content
	 * Filter out certain HTML tags
	 *
	 * @param Fishpig_Wordpress_Model_Post_Comment $comment
	 * @return string
	 */
	public function getCommentContent(Fishpig_Wordpress_Model_Post_Comment $comment)
	{
		$content = strip_tags(trim($comment->getCommentContent()), $this->getAllowedHtmlTags());
		
		return $this->canConvertNewLines()
			? nl2br($content)
			: $content;
	}
	
	
	public function getCommentsFormHtml()
	{
		return $this->getFormHtml();
	}
	
	/**
	 * Returns the HTML for the comment form
	 *
	 * @return string
	 */
	public function getFormHtml()
	{
		if ($this->isCommentsEnabled()) {
			return $this->getChildHtml('form');
		}
		
		return '';
	}
	
	
	/**
	 * Get the HTML for the pager block
	 *
	 * @return null|string
	 */
	public function getPagerHtml()
	{
		if ($this->helper('wordpress')->getWpOption('page_comments', false)) {
			return $this->getChildHtml('pager');
		}
	}
	
	/**
	 * Retrieve the allowed HTML tags as a string
	 *
	  * @return string
	  */
	public function getAllowedHtmlTags()
	{
		if (!$this->hasAllowedHtmlTags()) {
			return '<a><abbr><acronym><b><blockquote><cite><code><del><em><i><q><strike><strong>';
		}
		
		return $this->_getData('allowed_html_tags');
	}
	
	/**
	 * Determine whether to convert new lines to <br /> tags
	 * To disable this feature, call self::setConvertNewLines(false)
	 *
	 * @return bool
	 */
	public function canConvertNewLines()
	{
		return !$this->hasConvertNewLines() || (int)$this->getConvertNewLines() !== false;
	}
}
