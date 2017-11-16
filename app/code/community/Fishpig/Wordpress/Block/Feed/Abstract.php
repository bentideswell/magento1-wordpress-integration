<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Block_Feed_Abstract extends Fishpig_Wordpress_Block_Abstract
{
	/*
	 *
	 * @const string
	 */
	const IMG_WRAPPER = '<!-- Image=%s ID=%d -->';
	const IMG_WRAPPER_REGEX = '/(<!-- Image=(.*) ID=[0-9]+ -->)]]><\/description>/U';
	
	/**
	 *
	 * @param $feed
	 * @return
	 */
	abstract protected function _addEntriesToFeed($feed);
	
	/**
	 * Generate and return the feed
	 *
	 * @return string
	 */
	protected function _toHtml()
	{
		$feed = $this->getFeedWriter()->export($this->getFeedType());

		return $feed;
		
		/*
		if (strpos($feed, 'Image=') !== false) {
			if (preg_match_all(self::IMG_WRAPPER_REGEX, $feed, $matches)) {
				foreach($matches[0] as $it => $match) {
					$mediaTag = sprintf('<media:thumbnail url="%s"/>', $matches[2][$it]);
					
					$feed = str_replace($match, $match . "\n      " . $mediaTag, $feed);

					// Remove the image tag
					$feed = str_replace($matches[1][$it], '', $feed);
				}

			}
			
			$feed = preg_replace('/[\s]+(]]><\/description>)/', '$1', $feed);
			 
		}
		 
		return $feed;
		*/
	}
	
	/**
	 * Retrieve the items array
	 *
	 * @return array
	 */
	public function getFeedWriter()
	{
		$feed = new Zend_Feed_Writer_Feed;

		$feed->setTitle($this->getTitle());
		$feed->setLink($this->getLink());
		$feed->setDescription($this->getDescription());
		$feed->setFeedLink($this->getFeedLink(), 'atom');
		$feed->setDateModified(time());
		$feed->setLanguage($this->getLanguage());
		$feed->setGenerator($this->getGenerator());
		$this->_addEntriesToFeed($feed);
			
		return $feed;
	}

	/**
	 * Allow subclasses to filter items
	 *
	 * @return $this
	 */
	protected function _prepareItemCollection($collection)
	{
		$collection->setPageSize(
			Mage::helper('wordpress')->getWpOption('posts_per_rss', 10)
		);

		return $this;
	}
		
	/**
	 * Retrieve the blog charset
	 *
	 * @return string
	 */
	 public function getCharset()
	 {
		 return 'UTF-8';
	 }

	/**
	 * Retrieve the feed title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if (($blogName = $this->decode(trim(Mage::helper('wordpress')->getWpOption('blogname')))) !== '') {
			return $blogName;
		}
		
		if (($storeName = $this->decode(trim(Mage::getStoreConfig('general/store_information/name')))) !== '') {
			return $storeName . ' ' . $this->__('Blog Feed');
		}
		
		return $this->__('Blog Feed');
	}

	/**
	 * Retrieve the feed link
	 *
	 * @return string
	 */	
	public function getLink()
	{
		return Mage::helper('wordpress')->getUrl();
	}

	/**
	 * Retrieve the feed description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		if (($description = trim($this->decode(Mage::helper('wordpress')->getWpOption('blogdescription')))) !== '') {
			return $description;
		}
	
		return $this->getTitle();
	}

	/**
	 * Retrieve the feed language
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		if (($language = trim(Mage::helper('wordpress')->getWpOption('rss_language'))) !== '') {
			return $language;
		}
		
		return 'en-US';
	}

	/**
	* Decode a values html entities
	*
	* @param string $value
	* @return string
	*/
	public function decode($value)
	{
		return html_entity_decode($value, ENT_NOQUOTES, $this->getCharset());
	}
	
	/**
	 * Retrieve the generator value
	 *
	 * @return string
	 */
	public function getGenerator()
	{
		return 'http://fishpig.co.uk/magento/wordpress-integration/?v='
			. (string)Mage::getConfig()->getNode('modules/Fishpig_Wordpress/version');
	}
	
	/**
	 * Retrieve the feed link
	 *
	 * @return string
	 */
	public function getFeedLink()
	{
		return Mage::helper('core/url')->getCurrentUrl();
	}

	/**
	 * Retrieve the feed type
	 *
	 * @return string
	 */
	public function getFeedType()
	{
		$validTypes = array(
			'rss',
			'atom',
		);
		
		if (in_array($this->_getData('feed_type'), $validTypes)) {
			return $this->_getData('feed_type');
		}
		
		return 'rss';
	}
}
