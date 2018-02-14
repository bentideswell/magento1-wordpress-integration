<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Router extends Fishpig_Wordpress_Helper_Abstract
{
	/**
	 * Cache for blog URI
	 * Can be changed
	 *
	 * @static string
	**/
	static protected $_blogUri = null;

	/**
	 * Retrieve the blog URI
	 * This is the whole URI after blog route
	 *
	 * @return string
	 */
	public function getBlogUri()
	{
		if (self::$_blogUri !== null) {
			return self::$_blogUri;	
		}

		$pathInfo = strtolower(trim($this->getRequest()->getPathInfo(), '/'));	
		
		if ($this->getBlogRoute() && strpos($pathInfo, $this->getBlogRoute()) !== 0) {
			return null;
		}

		$pathInfo = trim(substr($pathInfo, strlen($this->getBlogRoute())), '/');

		self::$_blogUri = '';
		
		if ($pathInfo === '') {
			return '';
		}
		
		$pathInfo = explode('/', $pathInfo);
		
		// Clean off pager and feed parts
		if (($key = array_search('page', $pathInfo)) !== false) {
			if (isset($pathInfo[($key+1)]) && preg_match("/[0-9]{1,}/", $pathInfo[($key+1)])) {
				$this->getRequest()->setParam('page', $pathInfo[($key+1)]);
				unset($pathInfo[($key+1)]);
				unset($pathInfo[$key]);
				
				$pathInfo = array_values($pathInfo);
			}
		}
		
		// Clean off feed and trackback variable
		if (($key = array_search('feed', $pathInfo)) !== false) {
			unset($pathInfo[$key]);
			
			if (isset($pathInfo[$key+1])) {
				unset($pathInfo[$key+1]);
			}

			$this->getRequest()->setParam('feed', 'rss2');
			$this->getRequest()->setParam('feed_type', 'rss2');
		}

		// Remove comments pager variable
		foreach($pathInfo as $i => $part) {
			$results = array();
			if (preg_match("/" . sprintf('^comment-page-%s$', '([0-9]{1,})') . "/", $part, $results)) {
				if (isset($results[1])) {
					unset($pathInfo[$i]);
				}
			}
		}

		// Clean off amp
		if (Mage::helper('wordpress')->isAddonInstalled('AMP')) {
			if (($key = array_search('amp', $pathInfo)) !== false) {
				if (!isset($pathInfo[($key+1)])) {
					$this->getRequest()->setParam('amp', 1);
					unset($pathInfo[$key]);
					
					$pathInfo = array_values($pathInfo);
				}
			}
		}
		
		if (count($pathInfo) == 1 && preg_match("/^[0-9]{1,8}$/", $pathInfo[0])) {
			$this->getRequest()->setParam('p', $pathInfo[0]);
			
			array_shift($pathInfo);
		}

		$uri = urldecode(implode('/', $pathInfo));
		
		self::$_blogUri = $uri;
	
		return $uri;
	}

	/**
	 * Set the Blog URI
	 *
	 * @param string $blogUri
	 * @return $this
	**/
	public function setBlogUri($blogUri)
	{
		self::$_blogUri = $blogUri;
		
		return $this;
	}
	
	/**
	 * Retrieve the page ID set via the query string
	 *
	 * @return int|null
	 */
	public function getPageId()
	{
		return $this->getRequest()->getParam('page_id');
	}
	
	/**
	 * Retrieve the request object
	 *
	 * @return
	 */
	public function getRequest()
	{
		return Mage::app()->getRequest();
	}
	
	/**
	 * Retrieve the search query variable name
	 *
	 * @return string
	 */
	public function getSearchVar()
	{
		return 's';
	}
	
	/**
	 * Retrieve the search route
	 *
	 * @return string
	 */
	public function getSearchRoute()
	{
		return 'search';
	}
	
	/**
	 * Retrieve the current search term
	 *
	 * @return string
	 */
	public function getSearchTerm($escape = false, $key = null)
	{
		if (is_null($key)) {
			$searchTerm = $this->getRequest()->getParam($this->getSearchVar());
		}
		else {
			$searchTerm = $this->getRequest()->getParam($key);
		}

		return $escape
			? Mage::helper('wordpress')->escapeHtml($searchTerm)
			: $searchTerm;
	}
	
	/**
	 * Generate an array of URI's based on $results
	 *
	 * @param array $results
	 * @return array
	 */
	public function generateRoutesFromArray($results, $prefix = '')
	{
		$objects = array();
		$byParent = array();

		foreach($results as $key => $result) {
			if (!$result['parent']) {
				$objects[$result['id']] = $result;
			}
			else {
				if (!isset($byParent[$result['parent']])) {
					$byParent[$result['parent']] = array();
				}

				$byParent[$result['parent']][$result['id']] = $result;
			}
		}
		
		if (count($objects) === 0) {
			return false;
		}

		$routes = array();
		
		foreach($objects as $objectId => $object) {
			if (($children = $this->_createArrayTree($objectId, $byParent)) !== false) {
				$objects[$objectId]['children'] = $children;
			}

			$routes += $this->_createLookupTable($objects[$objectId], $prefix);
		}
		
		return $routes;
	}
	
	/**
	 * Create a lookup table from an array tree
	 *
	 * @param array $node
	 * @param string $idField
	 * @param string $field
	 * @param string $prefix = ''
	 * @return array
	 */
	protected function _createLookupTable(&$node, $prefix = '')
	{
		if (!isset($node['id'])) {
			return array();
		}

		$urls = array(
			$node['id'] => ltrim($prefix . '/' . urldecode($node['url_key']), '/')
		);

		if (isset($node['children'])) {
			foreach($node['children'] as $childId => $child) {
				$urls += $this->_createLookupTable($child, $urls[$node['id']]);
			}
		}

		return $urls;
	}
	
	/**
	 * Create an array tree. This is used for creating static URL lookup tables
	 * for categories and pages
	 *
	 * @param int $id
	 * @param array $pool
	 * @param string $field = 'parent'
	 * @return false|array
	 */
	protected function _createArrayTree($id, &$pool)
	{
		if (isset($pool[$id]) && $pool[$id]) {
			$children = $pool[$id];
			
			unset($pool[$id]);
			
			foreach($children as $childId => $child) {
				unset($children[$childId]['parent']);
				if (($result = $this->_createArrayTree($childId, $pool)) !== false) {
					$children[$childId]['children'] = $result;
				}
			}

			return $children;
		}
		
		return false;
	}
	
	/**
	 * If a page is set as a custom homepage, get it's ID
	 *
	 * @return false|int
	 */
	public function getHomepagePageId()
	{
		if (Mage::helper('wordpress')->getWpOption('show_on_front') === 'page') {
			if ($pageId = Mage::helper('wordpress')->getWpOption('page_on_front')) {
				return $pageId;
			}
		}
		
		return false;
	}
	
	/**
	 * If a page is set as a custom homepage, get it's ID
	 *
	 * @return false|int
	 */
	public function getBlogPageId()
	{
		if (Mage::helper('wordpress')->getWpOption('show_on_front') === 'page') {
			if ($pageId = Mage::helper('wordpress')->getWpOption('page_for_posts')) {
				return $pageId;
			}
		}
		
		return false;
	}
}
