<?php
/**
 * @category Fishpig
 * @package Fishpig_Wordpress
 * @author Ben Tideswell <ben@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Adminhtml_Extend extends Mage_Core_Block_Text
implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
	/**
	 * Tracking string for GA
	 *
	 * @var const string
	 */
	const TRACKING_STRING = '?utm_source=%s&utm_medium=%s&utm_term=%s&utm_campaign=Extend';
	
	/**
	 * XML Data
	 *
	 * @var const string
	 */
	const DATA = "<config><Fishpig_Bolt><url><![CDATA[magento/extensions/full-page-cache/]]></url><image>bolt.png</image><title>Bolt</title><subtitle>Full Page Cache</subtitle><short_definition>Add enterprise level caching to Magento community with Bolt, Magento's fastest Full Page Cache extension.</short_definition></Fishpig_Bolt><Fishpig_Opti><url><![CDATA[magento/extensions/minify/]]></url><image>opti.png</image><title>Opti</title><subtitle>Minify Magento</subtitle><short_definition>Opti automatically minifies your HTML, CSS and Javascript and works on any server.</short_definition></Fishpig_Opti><Fishpig_NoBots><url><![CDATA[magento/extensions/block-robots-stop-spam/]]></url><image>nobots.png</image><title>NoBots</title><subtitle>Stop Magento Spam Bots</subtitle><short_definition>NoBots automatically blocks spam bots from your website giving you less spam and a faster server.</short_definition></Fishpig_NoBots><Fishpig_CrossLink><url><![CDATA[magento/extensions/seo-internal-links/]]></url><image>crosslink.png</image><title>Crosslink</title><subtitle>SEO Internal Links</subtitle><short_definition>Automatically cross link your products, categories, splash pages, CMS pages, blog posts and categories using Crosslinks.</short_definition></Fishpig_CrossLink><Fishpig_AttributeSplashPro><url><![CDATA[magento/extensions/attribute-splash-pro/]]></url><image>splash-pro.png</image><title>AttributeSplash Pro</title><subtitle>SEO Landing Pages</subtitle><short_definition>Create SEO landing pages quickly and easily using AttributeSplash Pro. Decide which products you want to display based on multiple attribute filters, category filters and price filters.</short_definition></Fishpig_AttributeSplashPro><Fishpig_BasketShipping><url><![CDATA[magento/extensions/automatically-set-shipping-method/]]></url><image>basket-shipping.png</image><title>Basket Shipping</title><subtitle>Automatically set the Shipping Method</subtitle><short_definition>Automatically set the shipping method as soon as your customer hits your shopping cart.</short_definition></Fishpig_BasketShipping><Fishpig_Wordpress_Addon_Multisite><url><![CDATA[magento/wordpress-integration/multisite/]]></url><image>wordpress-multisite.png</image><title>Multisite Integration</title><subtitle>Add a blog to each store</subtitle><require_multistore>1</require_multistore><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_Multisite><Fishpig_Wordpress_Addon_Root><url><![CDATA[magento/wordpress-integration/root/]]></url><image>root.gif</image><title>Root</title><subtitle>Use WordPress to create CMS pages</subtitle><short_definition>Remove the blog sub-directory from your integrated blog URLs.</short_definition><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_Root><Fishpig_Wordpress_Addon_VisualComposer><url><![CDATA[magento/wordpress-integration/visual-composer/]]></url><image>visual-composer.png</image><title>Visual Composer</title><subtitle>Add sliders, image galleries and more</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_VisualComposer><Fishpig_Wordpress_Addon_AMP><url><![CDATA[magento/wordpress-integration/amp/]]></url><image>amp.png</image><title>AMP</title><subtitle>Accelerated mobile pages</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_AMP><Fishpig_Wordpress_Addon_GravityForms><url><![CDATA[magento/wordpress-integration/gravity-forms/]]></url><image>gravity-forms.png</image><title>Gravity Forms</title><subtitle>Add awesome forms easily!</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_GravityForms><Fishpig_Wordpress_Addon_Tablepress><url><![CDATA[magento/wordpress-integration/tablepress/]]></url><image>tablepress.png</image><title>TablePress</title><subtitle>Add tables from the WP Admin</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_Tablepress><Fishpig_Wordpress_Addon_CPT><url><![CDATA[magento/wordpress-integration/post-types-taxonomies/]]></url><image>posttypes.png</image><title><![CDATA[Post Types &amp; Taxonomies]]></title><subtitle>Use post types and taxonomies</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_CPT><Fishpig_Wordpress_Addon_ACF><url><![CDATA[magento/wordpress-integration/advanced-custom-fields/]]></url><image>advanced-custom-fields.png</image><title>Advanced Custom Fields</title><subtitle>Supports ACF Free and Pro</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_ACF><Fishpig_Wordpress_Addon_BBPress><url><![CDATA[magento/wordpress-integration/bbpress/]]></url><image>bbpress.png</image><title>BBPress</title><subtitle>Advanced customer forums</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_BBPress><Fishpig_Wordpress><url><![CDATA[magento/wordpress-integration/]]></url><image>wordpress.png</image><title>WordPress Integration</title><subtitle>WordPress blog integration</subtitle><short_definition>Manage multiple blogs from a single WordPress Admin and integrate each blog with a different Magento store view</short_definition><require_multistore>0</require_multistore></Fishpig_Wordpress><Fishpig_Wordpress_Addon_CS><url><![CDATA[magento/wordpress-integration/customer-synchronisation/]]></url><image>cs.png</image><title>Customer Synchronisation</title><subtitle>WordPress Single Sign-on</subtitle><short_definition>Synchronise WordPress users and Magento customers and provide a single login for your customers.</short_definition><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_CS><Fishpig_Wordpress_Addon_ContactForm7><url><![CDATA[magento/wordpress-integration/contact-form-7/]]></url><image>cf7.png</image><title>Contact Form 7</title><subtitle>Easily add forms to your site</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_ContactForm7><Fishpig_Wordpress_Addon_EventsCalendar><url><![CDATA[magento/wordpress-integration/events-calendar/]]></url><image>eventscalendar.png</image><title>The Events Calendar</title><subtitle>Event management made easy.</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_EventsCalendar><Fishpig_Wordpress_Addon_RevolutionSlider><url><![CDATA[magento/wordpress-integration/revolution-slider/]]></url><image>revslider.png</image><title>Revolution Slider</title><subtitle>Drag and drop slider creation.</subtitle><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_RevolutionSlider><Fishpig_Wordpress_Addon_IntegratedSearch><url><![CDATA[magento/wordpress-integration/integrated-search/]]></url><image>integrated-search.png</image><title>Integrated Search</title><subtitle>Search products and posts</subtitle><short_definition>Integrate your Magento and WordPress search systems automatically and make searching your site easier for your customers.</short_definition><depends><Fishpig_Wordpress /></depends></Fishpig_Wordpress_Addon_IntegratedSearch><Fishpig_AttributeSplash_Addon_XmlSitemap><url><![CDATA[magento/extensions/attribute-splash-pages/xml-sitemap/]]></url><image>splash-pages.png</image><title>Attribute AttributeSplash</title><subtitle>XML Sitemap</subtitle><short_definition>Add an XML sitemap for your AttributeSplash Pages and AttributeSplash Group so search engines can index your pages.</short_definition><depends><Fishpig_AttributeSplash /></depends></Fishpig_AttributeSplash_Addon_XmlSitemap><Fishpig_AttributeSplash_Addon_QuickCreate><url><![CDATA[magento/extensions/attribute-splash-pages/quick-create/]]></url><image>splash-pages.png</image><title>Attribute AttributeSplash</title><subtitle>Quick Create</subtitle><short_definition>Quickly create AttributeSplash Pages for any attributes with this extension and save yourself from hours of manual work.</short_definition><depends><Fishpig_AttributeSplash /></depends></Fishpig_AttributeSplash_Addon_QuickCreate></config>";

	/**
	 * Base URL for links
	 *
	 * @var const string
	 */
	 const BASE_URL = 'http://fishpig.co.uk/';

	 /**
	  * The URL for the S3 bucket for images
	  *
	  * @var const string
	  */
	 const S3_BUCKET_URL = 'https://s3.amazonaws.com/FishPig-Extend/image/';
	 
	/**
	 * Cache for all available extensions
	 *
	 * @var array
	 */
	static protected $_extensions = null;
	
	/**
	 *
	 * @return 
	 */
	protected function _construct()
	{
		$this->setTemplate('small.phtml');

		return parent::_construct();
	}

	/**
	 *
	 * @return 
	 */
	protected function _beforeToHtml()
	{
		if ($this->getTemplate() === 'small.phtml') {
			$this->_setTextForSmallTemplate();
		}	
		else if ($this->getTemplate() === 'large.phtml') {
			$this->_setTextForLargeTemplate();
		}

		return parent::_beforeToHtml();
	}
	
	/**
	 *
	 * @return 
	 */
	protected function _setTextForLargeTemplate()
	{
		if ($extensions = $this->getSelectedExtensions()) {
			$html = '';

			foreach($extensions as $extension) {
				$innerHtml = $this->_createHtmlElement('a', array(
					'href' => $this->getTrackedUrl($extension, $this->getModule(), 'image'), 
					'class' => 'image'
					), 
					$this->_createHtmlElement('img', array('src' => $this->getImageUrl($extension)))
				)
				. $this->_createHtmlElement('h2', array(), 
					$this->_createHtmlElement('a', $this->getTrackedUrl($extension, $this->getModule(), 'title'),
						$this->_createHtmlElement('strong', null, $this->escapeHtml($this->getTitle($extension)))
						. ' ' . $this->_createHtmlElement('span', null, $this->escapeHtml($this->getSubTitle($extension)))
				))
				. $this->_createHtmlElement('p',  null, $this->getShortDefinition($extension))
				. $this->_createHtmlElement('div', null, $this->_createHtmlElement('a', $this->getTrackedUrl($extension, $this->getModule(), 'view-more'), 'View Add-On'));

				$html .= $this->_createHtmlElement(
					'li', 
					array('class' => 'item' . ($this->isLast($extension) ? ' last' : '')), 
					$this->_createHtmlElement('div', array('class' => 'pad'), $innerHtml)
				);
			}
			
			$html = $this->_createHtmlElement('div', array('id' => $this->getId()), $this->_createHtmlElement('ul', null, $html));
			
			$html .= $this->_createHtmlElement(
				'style', 
				array('type' => 'text/css'), 
				str_replace("{{ID}}", $this->getId(), "#{{ID}} { max-width: 1600px; margin: 50px auto 0; }
#{{ID}} ul { /*height: 1%; overflow: hidden; */text-align: center; }
#{{ID}} li.item { display: inline-block; width: 24.5%; }
#{{ID}} li.item .pad { padding: 10% 8%; border-right: 1px solid #ccc; }
#{{ID}} li.item.last .pad { border-right: 0px none; }
#{{ID}} li.item .image { display: block; margin-bottom: 10px; }
#{{ID}} h2 a { color: #000; text-decoration: none; font-family: Tahoma, Verdana, Arial;, }
#{{ID}} h2 strong { display: block; text-transform: uppercase; line-height: 1em; }
#{{ID}} h2 span { font-size: 70%; font-family: Georgia, 'Times New Roman'; font-style: italic; }
#{{ID}} p { min-height: 80px; }"));

			$this->setText($html);
		}
		
		return $this;
	}
	
	/**
	 *
	 * @return 
	 */
	protected function _setTextForSmallTemplate()
	{
		if ($extensions = $this->getSelectedExtensions()) {
			$html = '';

			foreach($extensions as $extension) {
				$innerHtml = $this->_createHtmlElement('a', array(
					'href' => $this->getTrackedUrl($extension, $this->getModule(), 'image'), 
					), 
					$this->_createHtmlElement('img', array('src' => $this->getImageUrl($extension)))
					. $this->_createHtmlElement('span', null, $this->escapeHtml($this->getTitle($extension)))
				)
				.$this->_createHtmlElement('p', array(
					
				
				
				), $this->escapeHtml($this->getSubtitle($extension)));
				
				

				$html .= $this->_createHtmlElement(
					'li', 
					array('class' => 'item' . ($this->isLast($extension) ? ' last' : '')), 
					$this->_createHtmlElement('div', array('class' => 'pad'), $innerHtml)
				);
			}
			
			$html = $this->_createHtmlElement('div', array('id' => $this->getId()), $this->_createHtmlElement('ul', null, $html));
			$html .= $this->_createHtmlElement('script', array('type' => 'text/javascript'), "decorateList($('" . $this->getId() . "').select('ul').first());");
			
			$html .= $this->_createHtmlElement(
				'style', 
				array('type' => 'text/css'), 
				str_replace("{{ID}}", $this->getId(), "#{{ID}} { margin: 0; }
#{{ID}} ul { height: 1%; overflow: hidden;  }
#{{ID}} li.item { margin: 0 0 10px; width: 16.5%; float: left; border-right:1px solid #ddd; }
#{{ID}} li.item.last { border-right: 0; }
#{{ID}} li.item .pad {padding: 10px; height: 1%; overflow: hidden; margin: 0 5px 0 0; }
#{{ID}} li.item.even .pad { margin: 0 0 0 5px; }
#{{ID}} li.item a { display: block; text-align: center; text-decoration: none; }
#{{ID}} li.item img { max-height: 60px; }
#{{ID}} li.item a span { font-size: 14px; font-family: Tahoma, Verdana, Arial; line-height: 1em; margin: 5px 0 2px; display: block;}
#{{ID}} li.item p { text-align: center; } "));

			$this->setText($html);
		}
		
		return $this;
	}
	
	/**
	 * Retrieve extensions set via XML
	 *
	 * @return array
	 */
	public function getSelectedExtensions()
	{
		return $this->getExtensions($this->getLimit(), $this->getPreferred() ? ($this->getPreferred()) : null);	
	}

	/**
	 * Retrieve the available extensions taking into account $count and $pref
	 *
	 * @param int $count = 0
	 * @param array $pref = array()
	 * @return false|array
	 */
	public function getExtensions($count = 0, array $pref = array(), $rand = false)
	{
		if (!isset($pref[0])) {
			$pref = array_keys($pref);
		}

		if (($pool = $this->_getAllExtensions()) !== false) {
			$winners = array();

			foreach($pref as $code) {
				if (isset($pool[$code])) {
					$winners[$code] = $pool[$code];
					unset($pool[$code]);
				}
				
				if (!$rand && $count > 0 && count($winners) >= $count) {
					break;
				}
			}
			
			if ($rand) {
				$winners = $this->shuffleArray($winners);

				if ($count > 0 && count($winners) > $count) {
					$xcount = count($winners);

					while($xcount-- > $count) {
						array_pop($winners);
					}
				}
			}
			
			while(count($winners) < $count && count($pool) > 0) {
				$code = key($pool);
				
				$winners[$code] = $pool[$code];
				unset($pool[$code]);
			}
					
			end($winners);
			
			$winners[key($winners)]['last'] = true;
	
			return $winners;
		}

		return false;
	}
	
	/**
	 * Retrieve all of the available extensions
	 *
	 * @return array
	 */
	protected function _getAllExtensions()
	{
		if (!is_null(self::$_extensions)) {
			return self::$_extensions;
		}
		
		$installedModules = array_keys((array)$this->_getConfig()->getNode('modules'));
		
		$config = json_decode(json_encode(simplexml_load_string(self::DATA, null, LIBXML_NOCDATA)), true);
		self::$_extensions = array();

		foreach($config as $code => $extension) {
			$extension['module'] = $code;
			$reqMultistore = isset($extension['require_multistore']) ? (int)$extension['require_multistore'] : null;

			if (in_array($code, $installedModules)) {
				continue;
			}
			else if (!is_null($reqMultistore) && $reqMultistore === (int)Mage::app()->isSingleStoreMode()) {
				continue;
			}
			else if (isset($extension['depends'])) {
				$depends = array_keys((array)$extension['depends']);

				if (count(array_diff($depends, $installedModules)) > 0) {
					continue;
				}
			}

			self::$_extensions[$code] = (array)$extension;
		}

		if (count(self::$_extensions) === 0) {
			self::$_extensions = false;
		}

		return self::$_extensions;
	}

	/**
	 * Retrieve the title of the extension
	 *
	 * @param array $e
	 * @return string
	 */
	public function getTitle(array $e = null)
	{
		// Being called as a tab
		if (is_null($e)) {
			return $this->_getData('title');
		}

		return $this->_getField($e, 'title');
	}
	
	/**
	 * Retrieve the subtitle of the extension
	 *
	 * @param array $e
	 * @return string
	 */
	public function getSubTitle(array $e)
	{
		return $this->_getField($e, 'subtitle');
	}

	/**
	 * Rertrieve the URL for $e with the tracking code
	 *
	 * @param array $e
	 * @param string $campaign
	 * @param string $source
	 * @param string $medium
	 * @return string
	 */
	public function getTrackedUrl(array $e, $source, $content = null)
	{
		$term = $this->_getField($e, 'module');	
		 
		$trackedUrl = sprintf(self::BASE_URL . $this->_getField($e, 'url') . self::TRACKING_STRING, $source, $this->getMedium(), $term);
		
		if (!is_null($content)) {
			$trackedUrl .= '&utm_content=' . $content;
		}
		
		return $trackedUrl;
	}
	
	/**
	 * Retrieve the utm_medium parameter
	 *
	 * @return string
	 */
	public function getMedium()
	{
		return $this->_getData('medium')
			? $this->_getData('medium')
			: 'Magento Admin';
	}
	
	/**
	 * Retrieve the short definition of the extension
	 *
	 * @param array $e
	 * @return string
	 */
	public function getShortDefinition(array $e)
	{
		return $this->_getField($e, 'short_definition');
	}
	
	/**
	 * Retrieve the image URL of the extension
	 *
	 * @param array $e
	 * @return string
	 */
	public function getImageUrl(array $e)
	{
		return self::S3_BUCKET_URL . $this->_getField($e, 'image');
	}
	
	/**
	 * Retrieve a field from the extension
	 *
	 * @param array $e
	 * @param string $field
	 * @return string
	 */
	protected function _getField(array $e, $field)
	{
		return $e && is_array($e) && isset($e[$field]) ? $e[$field] : '';
	}
	
	/**
	 * Determine wether $e is the last $e in the array
	 *
	 * @param array $e
	 * @return bool
	 */
	public function isLast(array $e)
	{
		return $this->_getField($e, 'last') === true;
	}

	/**
	 * Retrieve the Magento config model
	 *
	 * @return Mage_Core_Model_Config
	 */
	protected function _getConfig()
	{
		return Mage::app()->getConfig();
	}
	
	/**
	 * Retrieve the ID
	 *
	 * @return string
	 */
	public function getId()
	{
		if (!$this->_getData('id')) {
			$this->setId('fp-extend-' . rand(1111, 9999));
		}
		
		return $this->_getData('id');
	}

	/**
	 * Retrieve the full path to the template
	 *
	 * @return string
	 */
    public function getTemplateFile()
    {
    	if (($dir = $this->_getFPAdminDir()) !== false) {
	    	return $dir . 'template' . DS . $this->getTemplate();
		}
    }

	/**
	 * Set the template include path
	 *
	 * @param string $dir
	 * @return $this
	 */    
	public function setScriptPath($dir)
	{
		$this->_viewDir = '';
		
		return $this;
	}
	
	/**
	 * Retrieve any available FPAdmin directory
	 *
	 * @return false|string
	 */
	protected function _getFPAdminDir()
	{
		$candidates = array(
			$this->getModule(),
			'Fishpig_Wordpress',
			'Fishpig_AttributeSplash',
			'Fishpig_iBanners'
		);

		foreach(array_unique($candidates) as $candidate) {
			if (!$candidate) {
				continue;
			}

			$dir = Mage::getModuleDir('', $candidate) . DS . 'FPAdmin' . DS;
			
			if (is_dir($dir)) {
				return $dir;
			}
		}
		
		return false;
	}
	
	/**
	 * If tab, always show
	 *
	 * @return bool
	 */
	public function canShowTab()
	{
		return true;
	}
	
	/**
	 * Don't hide if a tab
	 *
	 * @return bool
	 */
	public function isHidden()
	{
		return false;
	}
	
	/**
	 * Retrieve the tab title
	 *
	 * @return string
	 */
	public function getTabTitle()
	{
		return $this->getTabLabel();
	}
	
	/**
	 * Retrieve the tab label
	 *
	 * @return string
	 */
	public function getTabLabel()
	{
		return $this->_getData('tab_label');
	}
	
	/**
	 * Determine whether to skip generate content
	 *
	 * @return bool
	 */
	public function getSkipGenerateContent()
	{
		return true;
	}
	
	/**
	 * Retrieve the tab class name
	 *
	 * @return string
	 */
	public function getTabClass()
	{
		if ($this->getSkipGenerateContent()) {
			return 'ajax';
		}
		
		return parent::getTabClass();
	}
	
	/**
	 * Retrieve the URL used to load the tab content
	 *
	 * @return string
	 */
	public function getTabUrl()
	{
		if ($tabUrl = $this->_getData('tab_url')) {
			return $this->getUrl($tabUrl);
		}
		
		return '#';
	}
	
	/**
	 * Legacy fix that stops the HTML output from displaying
	 *
	 * @param string $fileName
	 * @return string
	 */
    public function fetchView($fileName)
    {
    	return is_file($fileName)
    		? parent::fetchView($fileName)
    		: '';
    }
    
    /**
     * Shuffle an array and preserve the keys
     *
     * @param array $a
     * @return array
     */
	public function shuffleArray(array $a)
	{
		$keys = array_keys($a); 
		
		shuffle($keys); 

		$random = array(); 
		
		foreach ($keys as $key) { 
			$random[$key] = $a[$key]; 
		}
		
		return $random; 
	} 
	
	/**
	 * Create a HTML element
	 *
	 * @param string $element
	 * @param string|array|null $params = array
	 * @param string $content = ''
	 * @return string
	 */
	protected function _createHtmlElement($element, $params = array(), $content = '')
	{
		if (is_null($params)) {
			$params = array();
		}
		
		if (!is_array($params)) {
			if ($element === 'a') {
				$params = array('href' => $params);
			}
			else if ($element === 'img') {
				$params = array('img' => $params);
			}
		}
		
		if ($element === 'a') {
			$params = array_merge($params, array('target' => '_blank'));
		}
		else if ($element === 'img') {
			$params = array_merge($params, array('alt' => ''));
		}
		
		foreach($params as $key => $value) {
			$params[$key] = sprintf('%s="%s"', $key, $value);
		}
		
		$closer = in_array($element, array('img')) ? '' : '</' . $element . '>';

		return sprintf('<%s%s>%s%s', $element, count($params) > 0 ? ' ' . implode(' ', $params) : '', $content, $closer);
	}
}
