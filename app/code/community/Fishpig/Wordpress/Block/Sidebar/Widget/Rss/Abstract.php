<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Block_Sidebar_Widget_Rss_Abstract extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Must be declared in child class
	 * Returns URL of feed or false
	 *
	 * @return false|string
	 */
	abstract public function getFeedUrl();
	
	/**
	 * Retrieve the RSS feed from the URL
	 *
	 * @return array
	 */
	protected function _getRssFeed($url)
	{
		$cacheKey = md5(serialize($this->getData()) . $this->getMaxFeedItems() . $url);

		if (($data = $this->_loadCustomDataFromCache($cacheKey)) !== false) {
			return unserialize($data);
		}
			
		try {
			if ($feed = file_get_contents($url)) {
				$xml = new SimpleXmlElement($feed);

				$feed = new Varien_Object($this->_convertXmlToArray($xml->channel));

				$this->_saveCustomDataToCache(serialize($feed), $cacheKey);	
				
				return $feed;
			}
		}
		catch (Exception $e) {
			$this->helper('wordpress')->log($e);
		}	
		
		return false;
	}

	/**
	 * Retrieve the max number of feed items
	 *
	 * @return int
	 */
	public function getMaxFeedItems()
	{
		return 5;
	}
	
	/**
	 * Prepare a feed item
	 *
	 * @param array $item
	 * @return Varien_Object
	 */
	protected function _prepareFeedItem(array $item)
	{
		return new Varien_Object($item);
	}

	/**
	 * Load the RSS feed and items before the block is rendered
	 *
	 */
	protected function _beforeToHtml()
	{
		parent::_beforeToHtml();
		
		$this->setFeedReady(false);

		if ($this->getFeedUrl() !== false) {
			if (($feed = $this->_getRssFeed($this->getFeedUrl())) !== false) {
				$this->setFeedTitle($feed->getTitle());
				$this->setFeedLink($feed->getLink());
				$this->setFeedDescription($feed->getDescription());
				
				$buffer = $feed->getItem();

				if (count($buffer) > 0) {
					if (!isset($buffer[0]) && isset($buffer['title'])) {
						$buffer = array($buffer);
					}
					
					$items = array();
	
					foreach($buffer as $item) {
						if (($item = $this->_prepareFeedItem($item)) !== false) {
							$items[] = $item;
							$this->setFeedReady(true);
						}
					}
					
					if (count($items) > $this->getMaxFeedItems()) {
						$items = array_splice($items, 0, $this->getMaxFeedItems());
					}

					$this->setFeedItems($items);
				}
			}
		}	

		return $this;
	}
	/**
	 * Convert a SimpleXMLElement object to an array
	 *
	 * @param SimpleXMLElement $xml
	 * @param array $out
	 * @return array]
	 */
	protected function _convertXmlToArray($xml, $out = array())
	{
		foreach((array)$xml as $index => $node) {
			if (is_object($node)) {
				$out[$index] = $this->_convertXmlToArray($node);
			}
			else if (is_array($node)) {
				$out[$index] = $this->_convertXmlToArray($node);
			}
			else {
				$out[$index] = $node;
			}
		}
		
		return $out;
	}
	
	/**
	 * Retrieve the RSS items
	 *
	 * @deprecated 2.4.40
	 * @return false|array
	 */
	public function getRssItems()
	{
		if (($items = $this->getFeedItems()) !== false) {
			return $items;
		}
		
		return array();
	}
}
