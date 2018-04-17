<?php
/**
 * @category Fishpig
 * @package Fishpig_Wordpress
 * @license http://fishpig.co.uk/license.txt
 * @author Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_Observer extends Varien_Object
{
	/**
	 * Flag used to ensure observers only run once per cycle
	 *
	 * @var static array
	 */
	static protected $_singleton = array();

	/**
	 * Determine emulation method
	**/
	protected $_canEmulate = null;
	
	/**
	 * Save the associations
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */	
	public function saveAssociationsObserver(Varien_Event_Observer $observer)
	{
		if (!$this->_observerCanRun(__METHOD__)) {
			return false;
		}

		try {
			Mage::helper('wordpress/associations')->processObserver($observer);
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e);
		}
	}
	
	/**
	 * Inject links into the top navigation
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */
	public function injectTopmenuLinksObserver(Varien_Event_Observer $observer)
	{
		if (!$this->_observerCanRun(__METHOD__)) {
			return false;
		}
		
		if (Mage::getStoreConfigFlag('wordpress/menu/enabled')) {
			return $this->injectTopmenuLinks($observer->getEvent()->getMenu());
		}
	}

	/**
	 * Inject links into the Magento topmenu
	 *
	 * @param Varien_Data_Tree_Node $topmenu
	 * @return bool
	 */
	public function injectTopmenuLinks($topmenu, $menuId = null)
	{
		if (is_null($menuId)) {
			$menuId = Mage::getStoreConfig('wordpress/menu/id');
		}

		if (!$menuId) {
			return false;
		}

		$menu = Mage::getModel('wordpress/menu')->load($menuId);		
		
		if (!$menu->getId()) {
			return false;
		}

		return $menu->applyToTreeNode($topmenu);
	}

	/**
	 * Inject links into the Magento XML sitemap
	 *
	 * @param Varien_Data_Tree_Node $topmenu
	 * @return bool
	 */	
	public function injectXmlSitemapLinksObserver(Varien_Event_Observer $observer)
	{
		$sitemap = $observer
			->getEvent()
				->getSitemap();

		if (!$this->_observerCanRun(__METHOD__ . $sitemap->getStoreId())) {
			return false;
		}

		try {
			$emulationData = $this->_startEmulation($sitemap->getStoreId());
	
			if (!Mage::getStoreConfigFlag('wordpress/module/enabled', $sitemap->getStoreId())) {
				return false;
			}
	
			$sitemapFilename = Mage::getBaseDir() . '/' . ltrim($sitemap->getSitemapPath() . $sitemap->getSitemapFilename(), '/' . DS);
			
			if (!file_exists($sitemapFilename)) {
				return $this;
			}
			
			$xml = trim(file_get_contents($sitemapFilename));
			
			// Trim off trailing </urlset> tag so we can add more
			$xml = substr($xml, 0, -strlen('</urlset>'));
	
			// Add the blog homepage
			$xml .= sprintf(
				'<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
				htmlspecialchars(Mage::helper('wordpress')->getUrl()),
				Mage::getSingleton('core/date')->gmtDate('Y-m-d'),
				'daily',
				'1.0'
			);

			$posts = Mage::getResourceModel('wordpress/post_collection')
				->addIsViewableFilter()
				->setOrderByPostDate()
				->load();

			foreach($posts as $post) {
				$xml .= sprintf(
					'<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>',
					htmlspecialchars($post->getPermalink()),
					$post->getPostModifiedDate('Y-m-d'),
					'monthly',
					'0.5'
				);
			}
	
			$xml .= '</urlset>';
			
			@file_put_contents($sitemapFilename, $xml);
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e);
		}
		
		$this->_endEmulation($emulationData);

		return $this;
	}

	/**
	 * Emulate $storeId
	 *
	 * @param int $storeId
	 * @return array
	**/
	protected function _startEmulation($storeId)
	{
		if ($this->_canStartEnvironmentEmulation()) {
			return Mage::getSingleton('core/app_emulation')->startEnvironmentEmulation($storeId);
		}
		else {
			$store = Mage::app()->getStore();
			
			$emulationData = array(
				'id' => $store->getId(),
				'code' => $store->getCode()
			);

			$store->setId($storeId)->setCode('anything');
			
			return $emulationData;
		}
	}
	
	/**
	 * End emulation
	 *
	 * @param array $emulationData
	 * @return $this
	**/
	protected function _endEmulation($emulationData)
	{
		if ($this->_canStartEnvironmentEmulation()) {
			Mage::getSingleton('core/app_emulation')->stopEnvironmentEmulation($emulationData);
		}
		else {
			Mage::app()->getStore()->setId($emulationData['id'])->setCode($emulationData['code']);
		}
		
		return $this;
	}
	
	/**
	 * Determine emulation method
	 *
	 * @return bool
	**/
	protected function _canStartEnvironmentEmulation()
	{
		if ($this->_canEmulate === null) {
			try {
				$this->_canEmulate = Mage::getSingleton('core/app_emulation') instanceof Mage_Core_Model_App_Emulation;
			}
			catch (Exception $e) {
				$this->_canEmulate = false;
			}
		}
		
		return $this->_canEmulate;
	}
	
	/**
	 * Initialise the configuration for the extension
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */		
	public function initWordpressConfigObserver(Varien_Event_Observer $observer)
	{
		return $this;
	}
	
	/**
	 * Inject content (JS, CSS) from WordPress
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 **/
	public function injectWordPressContentObserver(Varien_Event_Observer $observer)
	{
		if (!$this->_observerCanRun(__METHOD__)) {
			return $this;
		}
		
		if (Mage::helper('wordpress')->isApiRequest()) {
			return $this;
		}

		$bodyHtml = $observer->getEvent()
			->getFront()
				->getResponse()
					->getBody();

#   Add support for WPBakery Frontend Editor
#   Not stable yet
#		$bodyHtml = preg_replace('/<script[^>]{0,}>.*<\/script>/sU', '', $bodyHtml);
		
		if (Mage::helper('wordpress')->isAddonInstalled('PluginShortcodeWidget')) {
			$assets = Mage::getSingleton('wp_addon_pluginshortcodewidget/observer')->getAssets($bodyHtml);

			if (!$this->_canIncludeJquery()) {
				foreach($assets as $key => $value) {
					if (strpos($value, '/wp-includes/js/jquery/jquery.js') || strpos($value, '/wp-includes/js/jquery/jquery-migrate.min.js')) {
						unset($assets[$key]);
					}
				}
			}
			
			if (!$this->_canIncludeUnderscore()) {
				foreach($assets as $key => $value) {
					if (strpos($value, '/wp-includes/js/underscore.min.js') !== false) {
						unset($assets[$key]);
					}
				}
			}

			if (count($assets) === 0) {
				return $this;
			}
		}
		else {
			if (!($modulesConfigObjects = Mage::getConfig()->getNode('wordpress/core/modules'))) {
				return $this;
			}
			
			$modules = array_keys($modulesConfigObjects->asArray());
			$assets = array();
	
			foreach($modules as $module) {
				if ($code = Mage::getSingleton($module . '/observer')->getAssets($bodyHtml)) {
					$code = $this->_cleanAssetArray($code);

					foreach((array)$code as $asset) {
						$asset = trim($asset);

						if (!in_array($asset, $assets)) {
							$assets[] = $asset;
						}
					}
				}
			}

			if (count($assets) === 0) {
				return $this;
			}
	
			$baseUrl = Mage::helper('wordpress')->getBaseUrl();
			$jsTemplate = '<script type="text/javascript" src="%s"></script>';

			if ($this->_canIncludeUnderscore()) {
				array_unshift($assets, sprintf($jsTemplate, $baseUrl . 'wp-includes/js/underscore.min.js?ver=1.6.0'));
			}

			if ($this->_canIncludeJquery()) {
				array_unshift($assets, sprintf($jsTemplate, $baseUrl . 'wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'));
				array_unshift($assets, sprintf($jsTemplate, $baseUrl . 'wp-includes/js/jquery/jquery.js?ver=1.12.4'));
			}
		}

		if ($assets) {
			$observer->getEvent()
				->getFront()
					->getResponse()
						->setBody(str_replace('</body>', PHP_EOL . PHP_EOL . implode('', $assets) . '</body>', $bodyHtml));
		}

		return $this;
	}

	/*
	 *
	 *
	 * @return bool
	 */
	protected function _canIncludeJquery()
	{
		return false;
		return Mage::getStoreConfigFlag('wordpress/misc/include_jquery');
	}
	
	/*
	 *
	 *
	 * @return bool
	 */
	protected function _canIncludeUnderscore()
	{
		return false;
		return Mage::getStoreConfigFlag('wordpress/misc/include_underscore');
	}
	
	/*
	 * Clean the asset array into a single level array
	 *
	 * @param  array $assets
	 * @return array
	 */
	protected function _cleanAssetArray($assets)
	{
		$buffer = array();
		
		foreach($assets as $key => $value) {
			if (!is_array($value)) {
				$buffer[] = $value;
			}
			else {
				foreach($this->_cleanAssetArray($value) as $tvalue) {
					$buffer[] = $tvalue;
				}
			}
		}
		
		return $buffer;
	}

	/**
	 * Determine whether the observer method can run
	 * This stops methods being called twice in a single cycle
	 *
	 * @param string $method
	 * @return bool
	 */
	protected function _observerCanRun($method)
	{
		if (!isset(self::$_singleton[$method])) {
			self::$_singleton[$method] = true;
			
			return true;
		}
		
		return false;
	}
}
