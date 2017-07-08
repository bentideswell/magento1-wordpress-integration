<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_View_Comment_Wrapper extends Fishpig_Wordpress_Block_Post_View_Comment_Abstract
{
	/**
	 * Setup the pager and comments form blocks
	 *
	 * @return $this
	 */
	protected function _beforeToHtml()
	{
		if (!$this->getTemplate()) {
			$this->setTemplate('wordpress/post/view/comment/wrapper.phtml');
		}

		if ($this->getCommentCount() > 0 && ($commentsBlock = $this->getChild('comment_list')) !== false) {
			$commentsBlock->setComments($this->getComments());
		}	
			
		if ($this->getCommentCount() > 0 && ($pagerBlock = $this->getChild('pager')) !== false) {
			$pagerBlock->setCollection($this->getComments());
		}

		if (($form = $this->getChild('form')) !== false) {
			$form->setPost($this->getPost());
		}

		parent::_beforeToHtml();
	}
	
	/**
	 * Get the comments HTML
	 *
	 * @return string
	 */
	public function getCommentsHtml()
	{
		return $this->getChildHtml('comment_list');
	}
}
