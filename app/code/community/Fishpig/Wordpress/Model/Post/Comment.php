<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Post_Comment extends Fishpig_Wordpress_Model_Abstract
{
	/**
	 * Base URL used for Gravatar images
	 *
	 * @var const string
	 */
	const GRAVATAR_BASE_URL = 'http://www.gravatar.com/avatar/';
	const GRAVATAR_BASE_URL_SECURE = 'https://secure.gravatar.com/avatar/';
	
	public function _construct()
	{
		$this->_init('wordpress/post_comment');
	}

	/**
	 * Retrieve the post that this comment is associated to
	 *
	 * @return Fishpig_Wordpress_Model_Post
	 */
	public function getPost()
	{
		if (!$this->hasPost()) {
			$post = Mage::getModel('wordpress/post')
				->setPostType('*')
				->load((int)$this->getData('comment_post_ID'));

			$this->setPost($post->getId() ? $post : false);
		}
		
		return $this->getData('post');
	}

	/**
	 * Returns the comment date
	 * If no format is specified, the default format is used from the Magento config
	 *
	 * @return string
	 */
	public function getCommentDate($format = null)
	{
		return Mage::helper('wordpress')->formatDate($this->getData('comment_date'), $format);
	}
	
	/**
	 * Returns the comment time
	 * If no format is specified, the default format is used from the Magento config
	 *
	 * @return string
	 */
	public function getCommentTime($format = null)
	{
		return Mage::helper('wordpress')->formatTime($this->getData('comment_date'), $format);
	}
	
	/**
	 * Return the URL for the comment author
	 *
	 * @return string
	 */
	public function getCommentAuthorUrl()
	{
		if ($url = $this->_getData('comment_author_url')) {
			if (strpos($url, 'http') !== 0) {
				$url = 'http://' . $url;
			}
			
			return $url;
		}
		
		return '#';
	}
	
	/**
	 * Get the comment GUID
	 *
	 * @return string
	 */	
	public function getGuid()
	{
		return Mage::helper('wordpress')->getUrl('?p='. $this->getPost()->getId() . '#comment-' . $this->getId());
	}
	
	/**
	 * Retrieve the URL for this comment
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if (!$this->hasUrl()) {
			if ($post = $this->getPost()) {
				$pageId = '';
				
				if (Mage::helper('wordpress')->getWpOption('page_comments')) {
					$pageId = '/comment-page-' . $this->getCommentPageId();
				}
				
				$fragment = '#comment-' . $this->getId();

				if ($post->getTypeInstance()->permalinkHasTrainingSlash()) {
					$fragment = '/' . $fragment;
				}

				$this->setUrl(rtrim($post->getUrl(), '/') . $pageId . $fragment);
			}
		}
		
		return $this->getData('url');
	}
	
	/**
	 * Retrieve the page number that the comment is on
	 *
	 * @return int
	 */
	public function getCommentPageId()
	{
		if (!$this->hasCommentPageId()) {
			$this->setCommentPageId(1);
			if ($post = $this->getPost()) {
				$totalComments = count($post->getComments());
				$commentsPerPage = Mage::helper('wordpress')->getWpOption('comments_per_page', 50);

				if ($commentsPerPage > 0 && $totalComments > $commentsPerPage) {
					$it = 0;
					
					foreach($post->getComments() as $comment) { ++$it; 
						if ($this->getId() == $comment->getId()) {
							$position = $it;
							break;
						}
					}
				
					$this->setCommentPageId(ceil($position / $commentsPerPage));
				}
				else {
					$this->setCommentPageId(1);
				}
			}
		}
		
		return $this->getData('comment_page_id');
	}
	
	/**
	 * Retrieve the child comments
	 *
	 * @return Varien_Data_Collection
	 */
	public function getChildrenComments()
	{
		return $this->getCollection()
			->addCommentApprovedFilter()
			->addParentCommentFilter($this->getId())
			->addOrderByDate();
	}
	
	/**
	 * Retrieve the Gravatar URL for the comment
	 *
	 * @return null|string
	 */
	public function getAvatarUrl($size = 50)
	{
		if (!$this->hasGravatarUrl()) {
			if (Mage::helper('wordpress')->getWpOption('show_avatars')) {
				if ($this->getCommentAuthorEmail()) {
					$params = array(
						'r' => Mage::helper('wordpress')->getWpOption('avatar_rating'),
						's' => (int)$size,
						'd' => Mage::helper('wordpress')->getWpOption('avatar_default'),
						'v' => 45345
					);

					$baseUrl = Mage::app()->getStore()->isCurrentlySecure()
						? self::GRAVATAR_BASE_URL_SECURE
						: self::GRAVATAR_BASE_URL;
						
					$url = $baseUrl
						. md5(strtolower($this->getCommentAuthorEmail()))
						. '/?' . http_build_query($params);

					$this->setGravatarUrl($url);
				}
			}
		}
		
		return $this->_getData('gravatar_url');
	}
	
	/**
	 * Deprecated. Use self::getAvatarUrl($size)
	 *
	 * @param int $size
	 * @return string
	 */	
	public function getGravatarUrl($size = 50)
	{
		return $this->getAvatarUrl($size);
	}

	/**
	 * Determine whether the comment is approved
	 *
	 * @return bool
	 */
	public function isApproved()
	{
		return $this->_getData('comment_approved') === '1';
	}
	
	/**
	 * Retrieve the comment anchor
	 *
	 * @return string
	 */
	public function getAnchor()
	{
		$helper = Mage::helper('wordpress');
		
		return sprintf('<a href="%s" title="%s">%s</a>', $this->getUrl(), $helper->escapeHtml($this->getCommentAuthor()), $helper->escapeHtml($this->getPost()->getPostTitle()));
	}
}
