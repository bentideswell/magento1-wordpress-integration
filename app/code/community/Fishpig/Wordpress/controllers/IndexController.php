<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_IndexController extends Fishpig_Wordpress_Controller_Abstract
{
	/**
	 * Set the feed blocks
	 *
	 * @var string
	 */
	protected $_feedBlock = 'homepage';
	
	/**
	 * Used to do things en-masse
	 * eg. include canonical URL
	 *
	 * @return Varien_Object|Fishpig_Wordpress_Model_Page
	 */
	public function getEntityObject()
	{
		if (Mage::registry('wordpress_post')) {
			return Mage::registry('wordpress_post');
		}

		return new Varien_Object(array(
			'url' => Mage::helper('wordpress')->getUrl(),
		));
	}

	/**
	 * Display the blog homepage
	 *
	 * @return void
	 */
	public function indexAction()
	{
		/**
		 * If configured to display page as homepage,
		 * forward to post controller and set request variables
		 **/
		if (($post = Mage::registry('wordpress_post')) === null) {
			if (($postId = Mage::helper('wordpress/router')->getHomepagePageId()) !== false) {
				$this->getRequest()
					->setParam('id', $postId)
					->setParam('post_type', 'page')
					->setParam('is_homepage', 1);
	
				return $this->_forward('view', 'post');
			}
		}
		
		if ($this->_isPreview()) {
			return $this->_forward('view', 'post');
		}
		
		$this->_addCustomLayoutHandles(array(
			'wordpress_post_list',
			'wordpress_homepage',
			'wordpress_frontpage',
		));

		$this->_initLayout();
		$this->renderLayout();
	}
	
	/**
	 * Homepage is preview
	 *
	 * @return bool
	**/
	protected function _isPreview()
	{
		$keys = array('page_id', 'preview');
		
		foreach($keys as $key) {
			if ($this->getRequest()->getParam($key)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Display the blog robots.txt file
	 *
	 * @return void
	 */
	public function robotsAction()
	{
		if (($path = Mage::helper('wordpress')->getWordPressPath()) !== false) {
			$robotsFile = $path . 'robots.txt';

			if (is_file($robotsFile) && is_readable($robotsFile)) {
				if ($robotsTxt = file_get_contents($robotsFile)) {
					$this->getResponse()->setHeader('Content-Type', 'text/plain;charset=utf8');
					$this->getResponse()->setBody($robotsTxt);
				}
			}
		}
		
		if (!$this->getResponse()->getBody()) {
			$this->_forward('noRoute');
		}
	}

	/**
	 * Redirect the user to the WordPress Admin
	 *
	 * @return void
	 */
	public function wpAdminAction()
	{
		return $this->_redirectTo(Mage::helper('wordpress')->getAdminUrl());
	}
	
	/**
	 * Forward requests to the WordPress installation
	 *
	 * @return void
	 */
	public function forwardAction()
	{
		return $this->_forwardToWordPress('index.php?' . $_SERVER['QUERY_STRING']);
	}
	
	/**
	 * Forward requests for images
	 *
	 * @return void
	 */
	public function forwardFileAction()
	{
		return $this->_forwardToWordPress(Mage::helper('wordpress/router')->getBlogUri());
	}	

	/**
	 * Validate the new blog user
	 *
	 * @return void
	 */
	public function newBlogUserAction()
	{
	}
	
	/**
	 * Set the post password and redirect to the referring page
	 *
	 * @return void
	 */
	public function applyPostPasswordAction()
	{
		Mage::getSingleton('core/session')->setPostPassword($this->getRequest()->getPost('post_password'));
		
		if ($redirectTo = $this->getRequest()->getPost('redirect_to')) {
			$this->_redirectUrl($redirectTo);	
		}
		else {
			$this->_redirectReferer();
		}
	}
		
	/**
	 * Forces a redirect to the given URL
	 *
	 * @param string $url
	 * @return bool
	 */
	protected function _redirectTo($url)
	{
		return $this->getResponse()->setRedirect($url)->sendResponse();
	}

	/**
	 *
	 */
	public function wpjsonAction()
	{
		try {
			$coreModules = Mage::app()->getConfig()->getNode('wordpress/core/modules');
			
			if (!$coreModules) {
				throw new Exception('No WP Core modules installed.');
			}
			
			$coreModule = array_shift(array_keys((array)$coreModules));

			Mage::helper($coreModule . '/core')->isActive();

			exit;
		}
		catch (Exception $e) {
			return $this->_forward('noRoute');
		}
	}
}
