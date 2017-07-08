<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Sidebar_Widget_Rss extends Fishpig_Wordpress_Block_Sidebar_Widget_Rss_Abstract
{
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		if ($this->getFeed()) {
			return $this->getFeed()->getTitle();
		}
		
		return $this->__('RSS Feed');
	}

	public function getFeedUrl()
	{
		if (!$this->hasFeedUrl()) {
			$this->setFeedUrl(false);
			
			if (($url = trim($this->_getData('url'))) !== '') {
				$this->setFeedUrl($url);
			}
		}
		
		return $this->_getData('feed_url');
	}

	public function getMaxFeedItems()
	{
		return intval($this->_getData('items'));
	}
	
}
