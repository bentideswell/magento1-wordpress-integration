<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Controller_Abstract extends Mage_Core_Controller_Front_Action
{
	/**
	 * Blocks used to generate RSS feed items
	 *
	 * @var string
	 */
	 protected $_feedBlock = false;
	
	/**
	 * Storage for breadcrumbs
	 * These are added to the breadcrumbs block before rendering the page
	 *
	 * @var array
	 */
	protected $_crumbs = array();
	
	/**
	 * Used to do things en-masse
	 * eg. include canonical URL
	 *
	 * If null, means no entity required
	 * If false, means entity required but not set
	 *
	 * @return null|false|Mage_Core_Model_Abstract
	 */
	public function getEntityObject()
	{
		return null;
	}
	
	/**
	 * Ensure that the a database connection exists
	 * If not, do load the route
	 *
	 * @return $this
	 */
    public function preDispatch()
    {
	    if (Mage::registry('wordpress_controller')) {
		    Mage::unregister('wordpress_controller');
	    }

	    Mage::register('wordpress_controller', $this);
	    
    	parent::preDispatch();

		try {
			if (!$this->_canRunUsingConfig()) {
				$this->_forceForwardViaException('noRoute');
				return;
			}

			if ($this->getRequest()->getParam('feed_type')) {
				$this->getRequest()->setParam('feed', $this->getRequest()->getParam('feed_type')); // Legacy fix
				
				if (strpos(strtolower($this->getRequest()->getActionName()), 'feed') === false) {
					$this->_forceForwardViaException('feed');
					return;
				}
			}
		}
		catch (Mage_Core_Controller_Varien_Exception $e) {
			throw $e;
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e->getMessage());

			$this->_forceForwardViaException('noRoute');
			return;
		}

		// Check for redirects and forwards
		$transport = new Varien_Object();

		$className = get_class($this);

		$eventName = substr(
			$className, 
			strpos($className, '_', strpos($className, '_')+1)+1,
			-strlen('Controller')
		);
		
		$eventName = strtolower(trim(str_replace('Wordpress', '', $eventName), '_'));

		Mage::dispatchEvent(
			'wordpress_' . $eventName . '_controller_pre_dispatch_after', 
			array(
				'transport' => $transport,
				'action' => $this,
			)
		);
		
		if ($transport->getForward()) {
			return $this->_forward(
				$transport->getForward()->getAction(),
				$transport->getForward()->getController(),
				$transport->getForward()->getModule()
			);
		}
		
		return $this;
    }

	/**
	 * Determine whether the extension can run using the current config settings for this scope
	 * This will attempt to connect to the DB
	 *
	 * @return bool
	 */
	protected function _canRunUsingConfig()
	{
		if (!$this->isEnabledForStore()) {
			return false;
		}

		if (Mage::helper('wordpress/app')->getDbConnection() === false) {
			return false;
		}

		if (($object = $this->getEntityObject()) === false) {
			return false;
		}
		
		Mage::dispatchEvent($this->getFullActionName() . '_init_after', array('object' => $object, $this->getRequest()->getControllerName() => $object, 'action' => $this));

		return true;
	}
	
	/**
	 * Before rendering layout, apply root template (if set)
	 * and add various META items
	 *
	 * @param string $output = ''
	 * @return $this
	 */
    public function renderLayout($output='')
    {
		Mage::dispatchEvent('wordpress_render_layout_before', array('object' => $this->getEntityObject(), 'action' => $this));
		
		if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
			if ($entity = $this->getEntityObject()) {
				$headBlock->addItem('link_rel', ($entity->getCanonicalUrl() ? $entity->getCanonicalUrl() : $entity->getUrl()), 'rel="canonical"');
			}

			$headBlock->addItem('link_rel', 
				Mage::helper('wordpress')->getUrl('feed/'), 
				'rel="alternate" type="application/rss+xml" title="' . Mage::helper('wordpress')->getWpOption('blogname') . ' &raquo; Feed"'
			);
			
			$headBlock->addItem('link_rel', 
				Mage::helper('wordpress')->getUrl('comments/feed/'), 
				'rel="alternate" type="application/rss+xml" title="' . Mage::helper('wordpress')->getWpOption('blogname') . ' &raquo; Comments Feed"'
			);
		}

		if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
			if (Mage::helper('wordpress')->getWpOption('blog_public') !== '1') {
				$headBlock->setRobots('noindex,nofollow');
			}
		}

		$crumbCount = count($this->_crumbs);
		
		if ($crumbCount > 0 && ($block = $this->getLayout()->getBlock('breadcrumbs')) !== false) {
			foreach($this->_crumbs as $crumbName => $crumb) {
				if (--$crumbCount === 0 && isset($crumb[0]['link'])) {
					unset($crumb[0]['link']);
				}
				
				if ($crumb[0]['title']) {
					$block->addCrumb($crumbName, $crumb[0], $crumb[1]);
				}
			}
		}
		
		$this->_renderTitles();

		Mage::helper('wordpress/social')->addCodeToHead();
		
		return parent::renderLayout($output);
	}

	/**
	 * Loads layout and performs initialising tasls
	 *
	 */
	protected function _initLayout()
	{
		if (!$this->_isLayoutLoaded) {
			$this->loadLayout();
		}

		$this->_title()->_title(Mage::helper('wordpress')->getWpOption('blogname'));

		$this->addCrumb('home', array('link' => Mage::getUrl(), 'label' => $this->__('Home')));
		
		if (!$this->isFrontPage()) {
			$toplinkUrl = Mage::helper('wordpress')->getTopLinkUrl();
			
			if ($toplinkUrl !== Mage::getUrl()) {
				$this->addCrumb('blog', array('link' => $toplinkUrl, 'label' => $this->__(Mage::helper('wordpress')->getTopLinkLabel())));
			}
		}
		else {
			$this->addCrumb('blog', array('label' => $this->__(Mage::helper('wordpress')->getTopLinkLabel())));
		}
		
		if ($rootBlock = $this->getLayout()->getBlock('root')) {
			$rootBlock->addBodyClass('is-blog');
		}
		
		Mage::dispatchEvent('wordpress_init_layout_after', array('object' => $this->getEntityObject(), 'controller' => $this));
		Mage::dispatchEvent($this->getFullActionName() . '_init_layout_after', array('object' => $this->getEntityObject(), 'controller' => $this));

		return $this;
	}
	
	/**
	 * Adds a crumb to the breadcrumb trail
	 *
	 * @param string $crumbName
	 * @param array $crumbInfo
	 * @param string $after
	 */
	public function addCrumb($crumbName, array $crumbInfo, $after = false)
	{
		if (!isset($crumbInfo['title'])) {
			$crumbInfo['title'] = $crumbInfo['label'];
		}
		
		$this->_crumbs[$crumbName] = array($crumbInfo, $after);
		
		return $this;
	}

	/**
	 * Remove a breadcrumb by it's name
	 *
	 * @param string $crumbName
	 * @return $this
	 */
	public function removeCrumb($crumbName)
	{
		if (isset($this->_crumbs[$crumbName])) {
			unset($this->_crumbs[$crumbName]);
		}
		
		return $this;
	}
	
	/**
	 * Retrieve a breadcrumb
	 *
	 * @param string $crumbName
	 * @return array
	 */
	public function getCrumb($crumbName)
	{
		return isset($this->_crumbs[$crumbName]) ? $this->_crumbs[$crumbName] : false;
	}
	
	/**
	 * Get the breadcrumbs array
	 *
	 * @return array
	 */
	public function getCrumbs()
	{
		return $this->_crumbs;	
	}
	
	/**
	 * Adds custom layout handles
	 *
	 * @param array $handles = array()
	 */
	protected function _addCustomLayoutHandles(array $handles = array())
	{
		$update = $this->getLayout()->getUpdate();

		array_unshift($handles, 'wordpress_default');

		$storeHandlePrefix = 'STORE_' . Mage::app()->getStore()->getCode() . '_';
		$allHandles = array();
		
		foreach($handles as $it => $handle) {
			$allHandles[] = $handle;
			$allHandles[] = $storeHandlePrefix . $handle;
		}

		array_unshift($allHandles, 'default');
		
		foreach($allHandles as $handle) {
			$update->addHandle($handle);
		}
		
		$this->addActionLayoutHandles();
		
		$handles = $update->getHandles();

		$update->addHandle($storeHandlePrefix . array_pop($handles));
		
		$this->loadLayoutUpdates();		
		$this->generateLayoutXml();
		$this->generateLayoutBlocks();

		$this->_isLayoutLoaded = true;
		
		return $this;
	}
	
	/**
	 * Force the extension to remove any currently set titles
	 * This is likely to be called by SEO plugins (AllInOneSEO and Yoast SEO)
	 * so that they can rewrite the page titles
	 *
	 * @return $this
	 */
	public function ignoreAutomaticTitles()
	{
		$this->_titles = array();
		$this->_removeDefaultTitle = true;
		
		return $this;
	}
	
	/**
	 * Retrieve the router helper object
	 *
	 * @return Fishpig_Wordpress_Helper_Router
	 */
	public function getRouterHelper()
	{
		return Mage::helper('wordpress/router');
	}
	
	/**
	 * Determine whether the extension has been enabled for the current store
	 *
	 * @return bool
	 */
	public function isEnabledForStore()
	{
		return (!Mage::getStoreConfigFlag('advanced/modules_disable_output/Fishpig_Wordpress')
			&& Mage::getStoreConfigFlag('wordpress/module/enabled'));
	}
	
	/**
	 * Determine whether the current page is the blog homepage
	 *
	 * @return bool
	 */	
	public function isFrontPage()
	{
		return $this->getFullActionName() === 'wordpress_index_index';
	}
	
	/**
	 * Force Magento ro redirect to a different route
	 * This will happen without changing the current URL
	 *
	 * @param string $action
	 * @param string $controller = ''
	 * @param string $module = ''
	 * @param array $params = array
	 * @return void
	 */
	protected function _forceForwardViaException($action, $controller = '', $module = '', $params = array())
	{
		if ($action === 'noRoute') {
			$controller = 'index';
			$module = 'cms';
		}
		else {
			if ($controller === '') {
				$controller = $this->getRequest()->getControllerName();
			}
			
			if ($module === '') {
				$module = $this->getRequest()->getModuleName();
			}
		}
				
		$this->setFlag('', self::FLAG_NO_DISPATCH, true);
		
		$e = new Mage_Core_Controller_Varien_Exception();
	
		throw $e->prepareForward($action, $controller, $module, $params);
	}
	
	/**
	 * Forward a request to WordPress
	 *
	 * @param string $uri = ''
	 * @return $this
	 */
	protected function _forwardToWordPress($uri = '')
	{
		return $this->_redirectUrl(
			rtrim(Mage::helper('wordpress')->getWpOption('siteurl'), '/') . '/' . ltrim($uri, '/')
		);
	}
	
	/**
	 * Render the RSS Feed
	 *
	 * @return void
	 */
	public function feedAction()
	{
		if (($block = $this->_feedBlock) !== false) {
			if (strpos($block, '/') === false) {
				$block = 'wordpress/' . $block;
			}

			$this->getResponse()
				->setHeader('Content-Type', 'text/xml; charset=UTF-8')
				->setBody(
					$this->getLayout()->createBlock('wordpress/feed_post')->setSourceBlock($block)->setFeedType(
						$this->getRequest()->getParam('feed', 'rss2')
					)->toHtml()
				);
		}
		else {
			$this->_forward('noRoute');
		}
	}
	
	/**
	 * Display the comments feed
	 *
	 * @return void
	 */
	public function commentsFeedAction()
	{
		$this->getResponse()
			->setHeader('Content-Type', 'text/xml; charset=UTF-8')
			->setBody(
				$this->getLayout()->createBlock('wordpress/feed_post_comment')
					->setSource(Mage::registry('wordpress_post'))
					->setFeedType($this->getRequest()->getParam('feed', 'rss2'))
					->toHtml()
			);
	}
	
	/**
	 * Allows for legacy methods to be catered for
	 *
	 * @param string $method
	 * @param array $args
	 * @return mixed
	 */
	public function __call($method, $args)
	{
		$transport = new Varien_Object(array());
		
		Mage::dispatchEvent('wordpress_controller_method_invalid', array('method' => $method, 'args' => $args, 'object' => $this, 'transport' => $transport));
		
		if (!$transport->hasReturnValue()) {
			throw new Varien_Exception("Invalid method ".get_class($this)."::".$method."(".print_r($args,1).")");
		}
		
		return $transport->getReturnValue();
	}    
	
	/**
	 * If this method is called, you need to update your Magento WordPress Integration add-on extensions
	 *
	 * @return $this
	 **/
	public function includejQuery()
	{
		Mage::helper('wordpress')->log("You need to update your Magento WordPress Integration add-on extensions.");
		
		return $this;
	}
}
