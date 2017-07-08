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
					htmlspecialchars($post->getUrl()),
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

		$modulesConfigObjects = Mage::getConfig()->getNode('wordpress/core/modules');
		
		if (!$modulesConfigObjects) {
			return $this;
		}
		
		$modules = array_keys($modulesConfigObjects->asArray());
		$content = array();

		foreach($modules as $module) {
			if ($code = trim(Mage::getSingleton($module . '/observer')->getHeadFooterContent())) {
				$content[] = $code;
			}
		}
		
		if (count($content) === 0) {
			return $this;
		}

		$bodyHtml = $observer->getEvent()
			->getFront()
				->getResponse()
					->getBody();

		$baseUrl = Mage::helper('wordpress')->getBaseUrl();
		$jsTemplate = '<script type="text/javascript" src="%s"></script>';

		if (Mage::getStoreConfigFlag('wordpress/misc/include_underscore')) {
			array_unshift($content, sprintf($jsTemplate, $baseUrl . 'wp-includes/js/underscore.min.js?ver=1.6.0'));
		}

		if (Mage::getStoreConfigFlag('wordpress/misc/include_jquery')) {
			array_unshift($content, sprintf($jsTemplate, $baseUrl . 'wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'));
			array_unshift($content, sprintf($jsTemplate, $baseUrl . 'wp-includes/js/jquery/jquery.js?ver=1.12.4'));
		}

		$observer->getEvent()
			->getFront()
				->getResponse()
					->setBody(str_replace('</body>', implode('', $content) . '</body>', $bodyHtml));
		
		return $this;
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
