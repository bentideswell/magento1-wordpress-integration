<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
	/**
	 * Callback methods used to generate possible routes
	 *
	 * @var array
	 */
	protected $_routeCallbacks = array();

	/**
	 * Stores the static routes used by WordPress
	 *
	 * @var array
	 */
	protected $_staticRoutes = array();
	
	/**
	 * Create an instance of the router and add it to the queue
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */	
	public function initControllerObserver(Varien_Event_Observer $observer)
	{
		try {
#			Mage::helper('wordpress/app')->init();
			
			if ($this->isEnabled()) {
				$routerClass = get_class($this);
		
		   	    $observer->getEvent()
		   	    	->getFront()
		   	    		->addRouter('wordpress', new $routerClass);
			}
			
			return true;
	   	}
	   	catch (Exception $e) {
		   	Mage::helper('wordpress')->log($e);
	   	}

   	    return false;
	}
	
	/**
	 * Remove the AW_Blog route to stop conflicts
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */
    public function initControllerBeforeObserver(Varien_Event_Observer $observer)
    {
	    return $this;
	    /*
    	if (Mage::getDesign()->getArea() === 'frontend') {
	    	$node = Mage::getConfig()->getNode('global/events/controller_front_init_routers/observers');
    	
	    	if (isset($node->blog)) {
		    	unset($node->blog);

		    	Mage::getConfig()->setNode('modules/AW_Blog/active', 'false', true);
		    	Mage::getConfig()->setNode('frontend/routers/blog', null, true);
		    }
        }
        */

        return false;
    }
 
 	/**
	 * Attempt to match the current URI to this module
	 * If match found, set module, controller, action and dispatch
	 *
	 * @param Zend_Controller_Request_Http $request
	 * @return bool
	 */
	public function match(Zend_Controller_Request_Http $request)
	{
		try {
			Mage::helper('wordpress/app')->init();
			
			if (!Mage::helper('wordpress/app')->getDbConnection()) {
				return false;
			}

			if (!Mage::helper('wordpress')->isFullyIntegrated() || Mage::app()->getStore()->getCode() === 'admin') {
				return false;
			}
			
			if (($uri = Mage::helper('wordpress/router')->getBlogUri()) === null) {
				return false;	
			}

			Mage::dispatchEvent('wordpress_match_routes_before', array('router' => $this, 'uri' => $uri));

			# Call this again to check for changes
			if (($uri = Mage::helper('wordpress/router')->getBlogUri()) === null) {
				return false;	
			}
			
			if (!$uri) {
				$this->addRouteCallback(array($this, '_getHomepageRoutes'));	
			}
			
			$this->addRouteCallback(array($this, '_getSimpleRoutes'));
			$this->addRouteCallback(array($this, '_getPostRoutes'));
			$this->addRouteCallback(array($this, '_getTaxonomyRoutes'));

			if (($route = $this->_matchRoute($uri)) !== false) {
				return $this->setRoutePath($route['path'], $route['params']);
			}

			Mage::dispatchEvent('wordpress_match_routes_after', array('router' => $this, 'uri' => $uri));
		}
		catch (Exception $e) { 
			Mage::helper('wordpress')->log($e->getMessage());
		}

		return !is_null(Mage::app()->getRequest()->getModuleName())
			&& !is_null(Mage::app()->getRequest()->getControllerName())
			&& !is_null(Mage::app()->getRequest()->getActionName());
	}

	/**
	 * Get route data for different homepage URLs
	 *
	 * @param string $uri = ''
	 * @return $this
	 */
	protected function _getHomepageRoutes($uri = '')
	{
		if ($postId = Mage::app()->getRequest()->getParam('p')) {
			return $this->addRoute('', '*/post/view', array('p' => $postId, 'id' => $postId));
		}

		if (($pageId = $this->_getHomepagePageId()) !== false) {
			return $this->addRoute('', '*/post/view', array('id' => $pageId, 'post_type' => 'page', 'home' => 1));
		}
	
		$this->addRoute('', '*/index/index');
		
		return $this;
	}
	
	/**
	 * Generate the basic simple routes that power WP
	 *
	 * @param string $uri = ''
	 * @return false|$this
	 */	
	protected function _getSimpleRoutes($uri = '')
	{
		if (strpos($uri, 'ajax/') === 0) {
			$this->_getAjaxRoutes($uri);
		}
		
		$this->addRoute(array('/^wp-json\/(.*)$/' => array('json_route_data')), '*/index/wpjson');
		$this->addRoute(array('/^author\/([^\/]{1,})/' => array('author')), '*/author/view');
		$this->addRoute(array('/^([1-2]{1}[0-9]{3})\/([0-1]{1}[0-9]{1})$/' => array('year', 'month')), '*/archive/view');
		$this->addRoute(array('/^([1-2]{1}[0-9]{3})\/([0-1]{1}[0-9]{1})$/' => array('year', 'month')), '*/archive/view');
		$this->addRoute(array('/^([1-2]{1}[0-9]{3})\/([0-1]{1}[0-9]{1})\/([0-3]{1}[0-9]{1})$/' => array('year', 'month', 'day')), '*/archive/view');
		$this->addRoute(array('/^search\/(.*)$/' => array('s')), '*/search/index');
		$this->addRoute('search', '*/search/index', array('redirect_broken_url' => 1)); # Fix broken search URLs
		$this->addRoute('/^index.php/i', '*/index/forward');
		$this->addRoute('/^wp-content\/(.*)/i', '*/index/forwardFile');
		$this->addRoute('/^wp-includes\/(.*)/i', '*/index/forwardFile');
		$this->addRoute('/^wp-cron.php.*/', '*/index/forwardFile');
		$this->addRoute('/^wp-admin[\/]{0,1}$/', '*/index/wpAdmin');
		$this->addRoute('/^wp-pass.php.*/', '*/index/applyPostPassword');
		$this->addRoute('robots.txt', '*/index/robots');
		$this->addRoute('comments', '*/index/commentsFeed');
		$this->addRoute(array('/^newbloguser\/(.*)$/' => array('code')), '*/index/forwardNewBlogUser');

		return $this;
	}

	/**
	 * Retrieve routes for the AJAX methods
	 * These can be used to get another store's blogs blocks
	 *
	 * @param string $uri = ''
	 * @return $this
	 */
	protected function _getAjaxRoutes($uri = '')
	{
		$this->addRoute(array('/^ajax\/handle\/([^\/]{1,})[\/]{0,}$/' => array('handle')), '*/ajax/handle');
		$this->addRoute(array('/^ajax\/block\/([^\/]{1,})[\/]{0,}$/' => array('block')), '*/ajax/block');
		
		return $this;
	}

	/**
	 * Generate the post routes
	 *
	 * @param string $uri = ''
	 * @return false|$this
	 */
	protected function _getPostRoutes($uri = '')
	{
		if (($routes = Mage::getResourceSingleton('wordpress/post')->getPermalinksByUri($uri)) === false) {
			return false;
		}

		foreach($routes as $routeId => $route) {
			$this->addRoute(rtrim($route, '/'), '*/post/view', array('id' => $routeId));
			$this->addRoute(rtrim($route, '/') . '/all', '*/post/view', array('id' => $routeId));
		}

		return $this;
	}
	
	/**
	 * Get the custom taxonomy URI's
	 * First check whether a valid taxonomy exists in $uri
	 *
	 * @param string $uri = ''
	 * @return $this
	 */
	protected function _getTaxonomyRoutes($uri = '')
	{
		foreach(Mage::helper('wordpress/app')->getTaxonomies() as $taxonomy) {
			if (($routes = $taxonomy->getUris($uri)) !== false) {
				foreach($routes as $routeId => $route) {
					$this->addRoute($route, '*/term/view', array('id' => $routeId, 'taxonomy' => $taxonomy->getTaxonomyType()));
					$this->addRoute(rtrim($route, '/') . '/feed', '*/term/feed', array('id' => $routeId, 'taxonomy' => $taxonomy->getTaxonomyType()));
					
					if ($taxonomy->getExtraRoutes()) {
						foreach($taxonomy->getExtraRoutes() as $pattern => $newRoute) {
							$this->addRoute(str_replace('*', $route, $pattern), $newRoute, array('id' => $routeId, 'taxonomy' => $taxonomy->getTaxonomyType()));
						}
					}
				}
			}
		}

		return $this;
	}
	
	/**
	 * If a page is set as a custom homepage, get it's ID
	 *
	 * @return false|int
	 */
	protected function _getHomepagePageId()
	{
		return Mage::helper('wordpress/router')->getHomepagePageId();
	}
	
	/**
	 * Determine whether to add routes
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return (int)Mage::app()->getStore()->getId() !== 0;
	}
	
	/**
	 * Set the path and parameters ready for dispatch
	 *
	 * @param array $path
	 * @param array $params = array
	 * @return $this
	 */
	public function setRoutePath($path, array $params = array())
	{
		if (is_string($path)) {
			// Legacy
			$path = explode('/', $path);

			$path = array(
				'module' => $path[0] === '*' ? 'wordpress' : $path[0],
				'controller' => $path[1],
				'action' => $path[2],
			
			);
		}

		$request = Mage::app()->getRequest();

		$request->setModuleName($path['module'])
			->setRouteName($path['module'])
			->setControllerName($path['controller'])
			->setActionName($path['action']);

		foreach($params as $key => $value) {
			$request->setParam($key, $value);
		}

		$helper = Mage::helper('wordpress/router');
		
		$request->setAlias(
			Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS,
			ltrim($helper->getBlogRoute() . '/' . $helper->getBlogUri(), '/')
		);

		return true;
	}
	
	/**
	 * Execute callbacks and match generated routes against $uri
	 *
	 * @param string $uri = ''
	 * @return false|array
	 */
	protected function _matchRoute($uri = '')
	{
		$encodedUri = strtolower(str_replace('----slash----', '/', urlencode(str_replace('/', '----slash----', $uri))));
		
		foreach($this->_routeCallbacks as $callback) {
			$this->_staticRoutes = array();

			if (call_user_func($callback, $uri, $this) !== false) {
				foreach($this->_staticRoutes as $route => $data) {
					$match = false;

					if (substr($route, 0, 1) !== '/') {
						$match = $route === $encodedUri || $route === $uri;
					}
					else {
						if (preg_match($route, $uri, $matches)) {
							$match = true;
							
							if (isset($data['pattern_keys']) && is_array($data['pattern_keys'])) {
								array_shift($matches);
								
								if (!isset($data['params'])) {
									$data['params'] = array();
								}

								foreach($matches as $match) {
									if (($pkey = array_shift($data['pattern_keys'])) !== null) {
										$data['params'][$pkey] = $match;
									}
								}	
							}
						}
					}
					
					if ($match) {
						if (isset($data['params']['__redirect_to'])) {
							header('Location: ' . $data['params']['__redirect_to']);
							exit;	
						}
						
						return $data;
					}
				}	
			}
		}

		return false;
	}

	/**
	 * Add a callback method to generate new routes
	 *
	 * @param array
	 */
	public function addRouteCallback(array $callback)
	{
		$this->_routeCallbacks[] = $callback;
		
		return $this;
	}
	
	/**
	 * Add a generated route and it's details
	 *
	 * @param array|string $pattern
	 * @param string $path
	 * @param array|null $params = array()
	 * @return $this
	 */
	public function addRoute($pattern, $path, $params = array())
	{
		if (is_array($pattern)) {
			$keys = $pattern[key($pattern)];
			$pattern = key($pattern);
		}
		else {
			$keys = array();
		}

		$path = array_combine(array('module', 'controller', 'action'), explode('/', $path));
		
		if ($path['module'] === '*') {
			$path['module'] = 'wordpress';
		}

		$this->_staticRoutes[$pattern] = array(
			'path' => $path,
			'params' => $params,
			'pattern_keys' => $keys,
		);
		
		return $this;
	}
}
