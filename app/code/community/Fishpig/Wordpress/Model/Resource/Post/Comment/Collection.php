<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Post_Comment_Collection extends Fishpig_Wordpress_Model_Resource_Collection_Abstract
{
	/**
	 * Name prefix of events that are dispatched by model
	 *
	 * @var string
	*/
	protected $_eventPrefix = 'wordpress_post_comment_collection';
	
	/**
	 * Name of event parameter
	 *
	 * @var string
	*/
	protected $_eventObject = 'post_comments';
	
	public function _construct()
	{
		$this->_init('wordpress/post_comment');
	}

	/**
	 * Order the comments by date
	 *
	 * @param string $dir = null
	 * @return $this
	 */
	public function addOrderByDate($dir = null)
	{
		if (is_null($dir)) {
			$dir = Mage::helper('wordpress')->getWpOption('comment_order');
			$dir = in_array($dir, array('asc', 'desc')) ? $dir : 'asc';
		}
		
		$this->getSelect()->order('main_table.comment_date ' . $dir);
		
		return $this;
	}
	
	/**
	 * Add parent comment filter
	 *
	 * @param int $parentId = 0
	 * @return $this
	 */
	public function addParentCommentFilter($parentId = 0)
	{
		return $this->addFieldToFilter('comment_parent', $parentId);
	}
	
	/**
	  * Filters the collection of comments
	  * so only comments for a certain post are returned
	  *
	  * @return $this
	  */
	public function addPostIdFilter($postId)
	{
		return $this->addFieldToFilter('comment_post_ID', $postId);
	}
	
	/**
	 * Filter the collection by a user's ID
	 *
	 * @param int $userId
	 * @return $this
	 */
	public function addUserIdFilter($userId)
	{
		return $this->addFieldToFilter('user_id', $userId);
	}
	
	/**
	  * Filter the collection by the comment_author_email column
	  *
	  * @param string $email
	  * @return $this
	 */
	public function addCommentAuthorEmailFilter($email)
	{
		return $this->addFieldToFilter('comment_author_email', $email);
	}
	
	/**
	  * Filters the collection so only approved comments are returned
	  *
	  * @return $this
	 */
	public function addCommentApprovedFilter($status = 1)
	{
		return $this->addFieldToFilter('comment_approved', $status);
	}
}
