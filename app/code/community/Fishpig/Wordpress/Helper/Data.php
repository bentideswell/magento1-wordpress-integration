<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Data extends Fishpig_Wordpress_Helper_Abstract
{
	/**
	 * Retrieve the top link URL
	 *
	 * @return false|string
	 */
	public function getTopLinkUrl()
	{
		try {
			if ($this->isEnabled()) {
				if ($this->isFullyIntegrated()) {
					if ($this->_isCached('toplink_url')) {
						return $this->_cached('toplink_url');
					}
					
					$transport = new Varien_Object(array('toplink_url' => $this->getUrl()));
					
					Mage::dispatchEvent('wordpress_get_toplink_url', array('transport' => $transport));

					$this->_cache('toplink_url', $transport->getToplinkUrl());
					
					return $transport->getToplinkUrl();
				}

				return $this->getWpOption('home');
			}
		}
		catch (Exception $e) {
			$this->log('Magento & WordPress are not correctly integrated (see entry below).');
			$this->log($e->getMessage());
		}
		
		return false;
	}
	
	/**
	 * Retrieve the position for the top link
	 *
	 * @return false|int
	 */
	public function getTopLinkPosition()
	{
		if ($this->isEnabled()) {
			return (int)Mage::getStoreConfig('wordpress/toplink/position');
		}
		
		return false;
	}
	
	/**
	 * Returns the label for the top link
	 * This is also used for the breadcrumb
	 *
	 * @return false|string
	 */
	public function getTopLinkLabel()
	{
		if ($this->isEnabled()) {
			if ($this->_isCached('toplink_label')) {
				return $this->_cached('toplink_label');
			}
					
			$transport = new Varien_Object(array('toplink_label' => Mage::getStoreConfig('wordpress/toplink/label')));
			
			Mage::dispatchEvent('wordpress_get_toplink_label', array('transport' => $transport));

			$this->_cache('toplink_label', $transport->getToplinkLabel());
			
			return $transport->getToplinkLabel();
		}
		
		return false;
	}
	
	/**
	 * Returns the pretty version of the blog route
	 * This is deprecated. Instead, use self::getTopLinkLabel
	 *
	 * @return false|string
	 */
	public function getPrettyBlogRoute()
	{
		return $this->getTopLinkLabel();
	}
	
	/**
	  * Returns the URL Wordpress is installed on
	  *
	  * @param string $extra
	  * @return string
	  */
	public function getBaseUrl($extra = '')
	{
		return rtrim($this->getWpOption('siteurl'), '/') . '/' . $extra;
	}
	
	/**
	  * Get Wordpress Admin URL
	  *
	  */
	public function getAdminUrl($extra = null)
	{
		return $this->getBaseUrl('wp-admin/' . $extra);
	}
	
	/**
	 * Returns the given string prefixed with the Wordpress table prefix
	 *
	 * @return string
	 */
	public function getTableName($table)
	{
		return Mage::getSingleton('core/resource')->getTableName($table);
	}
	
	/**
	 * Determine whether the module is enabled
	 * This can be changed by going to System > Configuration > Advanced
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return Mage::getStoreConfigFlag('wordpress/module/enabled', Mage::helper('wordpress/app')->getStore()->getId())
			&& !Mage::getStoreConfig('advanced/modules_disable_output/Fishpig_Wordpress', Mage::helper('wordpress/app')->getStore()->getId());
	}
	
	/**
	  * Formats a Wordpress date string
	  *
	  */
	public function formatDate($date, $format = null, $f = false)
	{
		if ($format == null) {
			$format = $this->getDefaultDateFormat();
		}
		
		/**
		 * This allows you to translate month names rather than whole date strings
		 * eg. "March","Mars"
		 *
		 */
		$len = strlen($format);
		$out = '';
		
		for( $i = 0; $i < $len; $i++) {	
			$out .= $this->__(Mage::getModel('core/date')->date($format[$i], strtotime($date)));
		}
		
		return $out;
	}
	
	/**
	  * Formats a Wordpress date string
	  *
	  */
	public function formatTime($time, $format = null)
	{
		if ($format == null) {
			$format = $this->getDefaultTimeFormat();
		}
		
		return $this->formatDate($time, $format);
	}
	
	/**
	 * Split a date by spaces and translate
	 *
	 * @param string $date
	 * @param string $splitter = ' '
	 * @return string
	 */
	public function translateDate($date, $splitter = ' ')
	{
		$dates = explode($splitter, $date);
		
		foreach($dates as $it => $part) {
			$dates[$it] = $this->__($part);
		}
		
		return implode($splitter, $dates);
	}
	
	/**
	  * Return the default date formatting
	  *
	  */
	public function getDefaultDateFormat()
	{
		return $this->getWpOption('date_format', 'F jS, Y');
	}
	
	/**
	  * Return the default time formatting
	  *
	  */
	public function getDefaultTimeFormat()
	{
		return $this->getWpOption('time_format', 'g:ia');
	}
	
	/**
	 * Determine whether a WordPress plugin is enabled in the WP admin
	 *
	 * @param string $name
	 * @param bool $format
	 * @return bool
	 */
	public function isPluginEnabled($name)
	{
		return Mage::helper('wordpress/plugin')->isEnabled($name);
	}
	
	/**
	 * Determine whether to force single store
	 *
	 * @return bool
	 */
	public function forceSingleStore()
	{
		return Mage::getStoreConfigFlag('wordpress/integration/force_single_store', Mage::helper('wordpress/app')->getStore()->getId());
	}
	
	/**
	 * Determine whether Fishpig_WordpressMu can run
	 *
	 * @return bool
	 */
	public function isWordPressMU()
	{
		if (!$this->_isCached('is_wpmu')) {
			$this->_cache('is_wpmu', false);
			
			if ($this->isWordPressMUInstalled()) {
				$this->_cache('is_wpmu', Mage::helper('wp_addon_multisite')->canRun());
			}
		}
		
		return $this->_cached('is_wpmu');
	}

	/**
	 * Determine whether Fishpig_WordpressMu is installed
	 *
	 * @return bool
	 */
	public function isWordPressMUInstalled()
	{
		if (!$this->_isCached('is_wpmu_installed')) {
			$config = Mage::getConfig();

			$isInstalled = (string)$config->getNode('modules/Fishpig_Wordpress_Addon_Multisite/active') === 'true'
				|| (string)$config->getNode('modules/Fishpig_WordpressMu/active') === 'true';
				
			$this->_cache('is_wpmu_installed', $isInstalled);
		}
		
		return $this->_cached('is_wpmu_installed');
	}
	
	/**
	 * Retrieve the upload URL
	 *
	 * @return string
	 */
	public function getFileUploadUrl()
	{
		$url = $this->getWpOption('fileupload_url');
		
		if (!$url) {
			foreach(array('upload_url_path', 'upload_path') as $config) {
				if ($value = $this->getWpOption($config)) {
					if (strpos($value, 'http') === false) {
						if (substr($value, 0, 1) !== '/') {
							$url = $this->getBaseUrl($value);
						}
					}
					else {
						$url = $value;
					}

					break;
				}
			}
			
			if (!$url) {
				if ($this->isWordPressMU() && !Mage::helper('wpmultisite')->isDefaultBlog() && Mage::helper('wpmultisite')->getBlogId()) {
					$url = $this->getBaseUrl('wp-content/uploads/sites/' . Mage::helper('wpmultisite')->getBlogId() . '/');
				}
				else {
					$url = $this->getBaseUrl('wp-content/uploads/');
				}
			}
		}
		
		return rtrim($url, '/') . '/';
	}
	
	/**
	 * Retrieve the local path to file cache path
	 *
	 * @return string
	 */
	public function getFileCachePath()
	{
		return Mage::getBaseDir('var') . DS . 'wordpress' . DS;
	}
	
	/**
	 * Retrieve the path for the WordPress installation
	 * Return false if path is invalid
	 *
	 * @return false|string
	 */
	public function getWordPressPath()
	{
		$path = $this->getRawWordPressPath();
		
		return is_dir($path) && is_file($path . 'wp-config.php')
			? $path
			: false;
	}
	
	/**
	 * Retrieve the path for the WordPress installation
	 * Do not check, just return
	 *
	 * @return string
	 */
	public function getRawWordPressPath()
	{
		$path = rtrim(Mage::getStoreConfig('wordpress/integration/path', Mage::helper('wordpress/app')->getStore()->getId()), DS);

		if ($path === '') {
			return false;
		}

		if (substr($path, 0, 1) !== DS) {
			return Mage::getBaseDir() . DS . $path . DS;
		}

		return rtrim($path, DS) . DS;
	}
	
	/**
	 * Determine whether an addon is installed
	 *
	 * @param string $addon
	 * @return bool
	 */
	public function isAddonInstalled($addon)
	{
		if (strpos($addon, '_') === false) {
			$addon = 'Fishpig_Wordpress_Addon_' . $addon;
		}

		return (string)Mage::getConfig()->getNode('modules/' . $addon . '/active') === 'true';
	}
	
	/**
	 * Provides backwards compatibility for older Magento versions running Legacy
	 *
	 * @param string $data
	 * @param array $allowedTags = null
	 * @return string
	 */
	public function escapeHtml($data, $allowedTags = null)
	{
		return Mage::helper('core')->htmlEscape($data, $allowedTags);
	}
	
	/**
	 * Determine wether the Legacy add-on is installed
	 *
	 * @return bool
	 */
	public function isLegacy()
	{
		if ($this->_isCached('is_legacy')) {
			return $this->_cached('is_legacy');
		}
		
		$isLegacy = is_file(Mage::getBaseDir() . DS . 'app' . DS . 'etc' . DS . 'modules' . DS . 'Fishpig_Wordpress_Addon_Legacy.xml');
		
		$this->_cache('is_legacy', $isLegacy);
		
		return $isLegacy;
	}
	
	/**
	 * Determine whether the request is an API request
	 *
	 * @return bool
	**/
	public function isApiRequest()
	{
		return strpos(trim(Mage::app()->getRequest()->getPathInfo(), '/'), 'api/') === 0;
	}
	
	/*
	 * Get the current page type
	 *
	 * @return string
	 */
	/*
	public function getPageType()
	{
		if (!($controller = Mage::registry('wordpress_controller'))) {
			return false;
		}
		
		if ($controller instanceof Fishpig_Wordpress_IndexController) {
			return 'homepage';
		}
		else if ($post = Mage::registry('wordpress_post')) {
			return $post->isHomepagePage() || $post->isBlogListingPage() ? 'homepage' : $post->getPostType();
		}
		else if ($term = Mage::registry('wordpress_term')) {
			return $term->getTaxonomy();
		}
		else if ($archive = Mage::registry('wordpress_archive')) {
			return 'archive';
		}
		else if ($user = Mage::registry('wordpress_author')) {
			return 'author';
		}
	}
	*/
}
