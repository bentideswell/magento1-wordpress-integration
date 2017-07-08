<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_View_Comments extends Fishpig_Wordpress_Block_Post_View_Comment_Abstract
{
	/**
	 * Setup the pager and comments form blocks
	 *
	 */
	protected function _beforeToHtml()
	{
		if (!$this->getTemplate()) {
			$this->setTemplate('wordpress/post/view/comments.phtml');
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
	 * Get the HTML of the child comments
	 *
	 * @param Fishpig_Wordpress_Model_Post_Comment $comment
	 * @return string
	 */
	public function getChildrenCommentsHtml(Fishpig_Wordpress_Model_Post_Comment $comment)
	{
		return $this->getLayout()
			->createBlock('wordpress/post_view_comments')
			->setTemplate($this->getTemplate())
			->setParentId($comment->getId())
			->setComments($comment->getChildrenComments())
			->toHtml();
	}
}
