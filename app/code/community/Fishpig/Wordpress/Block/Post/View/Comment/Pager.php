<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_View_Comment_Pager extends Fishpig_Wordpress_Block_Post_List_Pager 
{
	/**
	 * Gets the comments per page limit
	 *
	 * @return int
	 */
	public function getLimit()
	{
		$this->_limit = $this->getRequest()->getParam('limit', Mage::helper('wordpress')->getWpOption('comments_per_page', 50));

		return $this->_limit;
	}

	/**
	 * Returns the available limits for the pager
	 * As Wordpress uses a fixed page size limit, this returns only 1 limit (the value set in WP admin)
	 * This effectively hides the 'Show 4/Show 10' drop down
	 *
	 * @return array
	 */
	public function getAvailableLimit()
	{
		return array($this->getPagerLimit() => $this->getPagerLimit());
	}
	
	/**
	 * Retrieve the variable used to generate URLs
	 *
	 * @return string
	 */
	public function getPageVarName()
	{
		return 'page';
	}
	
	/**
	 * Convert the URL to correct URL for the comments pager
	 *
	 * @return string
	 */
	public function getPagerUrl($params=array())
	{
		if (isset($params['page']) && $params['page'] != 1) {
			return rtrim($this->getPost()->getPermalink(), '/')
				. '/' . sprintf(trim($this->helper('wordpress/router')->getCommentPagerVarFormat(), '^$'), $params['page']) . '#comments';
		}
		
		return $this->getPost()->getPermalink() . '#comments';
	}
	
	/**
	 * Retrieve the post object
	 *
	 * @return Fishpig_Wordpress_Model_Post
	 */
	public function getPost()
	{
		return Mage::registry('wordpress_post');
	}
	
	/**
	 * Retrieve the current page ID
	 *
	 * @return int
	 */
	public function getCurrentPage()
	{
		if (!$this->hasCurrentPage()) {
			$results = array();
		
			if (preg_match("/comment-page-([0-9]{1,})$/", $this->helper('core/url')->getCurrentUrl(), $results)) {
				if (isset($results[1])) {
					$this->setCurrentPage($results[1]);
				}
			}
			else {
				$this->setCurrentPage(1);
			}
		}

		return $this->getData('current_page');
	}
}
