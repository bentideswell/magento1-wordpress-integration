<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Sidebar_Widget_Comments extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract 
{
	/**
	 * Retrieve the recent comments collection
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Comment_Collection
	 */
	public function getComments()
	{
		if (!$this->hasComments()) {
			$comments = Mage::getResourceModel('wordpress/post_comment_collection')
				->addCommentApprovedFilter()
				->addOrderByDate('desc');
			
			$comments->getSelect()->limit($this->getNumber() ? $this->getNumber() : 5 );
			
			$this->setComments($comments);
		}
		
		return $this->getData('comments');
	}
	
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->__('Recent Comments');
	}
}
