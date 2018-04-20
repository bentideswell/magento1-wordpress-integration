<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Addon_WordPressSEO_Helper_Data extends Fishpig_Wordpress_Helper_Plugin_Seo_Abstract
{
	/**
	 * A list of option fields used by the extension
	 * All fields are prefixed with wpseo_
	 *
	 * @var array
	 */
	protected $_optionFields = array('', 'titles', 'xml', 'social', 'rss', 'internallinks', 'permalinks');
	
	/**
	 * The value used to separate token's in the title
	 *
	 * @var string
	 */
	protected $_rewriteTitleToken = '%%';

	/**
	 * Cache for OG tags
	 *
	 * @var array
	 */
	protected $_openGraphTags = array();
	
	protected $_separatorOptions = array(
		'sc-dash'   => '-',
		'sc-ndash'  => '&ndash;',
		'sc-mdash'  => '&mdash;',
		'sc-middot' => '&middot;',
		'sc-bull'   => '&bull;',
		'sc-star'   => '*',
		'sc-smstar' => '&#8902;',
		'sc-pipe'   => '|',
		'sc-tilde'  => '~',
		'sc-laquo'  => '&laquo;',
		'sc-raquo'  => '&raquo;',
		'sc-lt'     => '&lt;',
		'sc-gt'     => '&gt;',
	);
	
	/**
	 * Automatically load the plugin options
	 *
	 */
	protected function _construct()
	{
		parent::_construct();

		$data = array();
		
		foreach($this->_optionFields as $key) {
			if ($key !== '') {
				$key = '_' . $key;
			}

			$options = Mage::helper('wordpress')->getWpOption('wpseo' . $key);
			
			if ($options) {
				$options = unserialize($options);

				foreach($options as $key => $value) {
					if (strpos($key, '-') !== false) {
						unset($options[$key]);
						$options[str_replace('-', '_', $key)] = $value;
					}
				}
				
				$data = array_merge($data, $options);
			}
		}

		$this->setData($data);
	}

	/**
	 * Determine whether All In One SEO is enabled
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return Mage::helper('wordpress')->isPluginEnabled('wordpress-seo/wp-seo.php') 
			|| Mage::helper('wordpress')->isPluginEnabled('wordpress-seo-premium/wp-seo-premium.php');
	}

	/**
	 * Perform global actions before the user_func has been called
	 *
	 * @return $this
	 */		
	protected function _beforeObserver()
	{
		$helper = Mage::helper('wordpress');
		
		$this->_applyOpenGraph(array(
			'locale' => Mage::app()->getLocale()->getLocaleCode(),
			'type' => 'blog',
			'title' => $helper->getWpOption('blogname'),
			'description' => $helper->getWpOption('blogdescription'),
			'url' => $helper->getUrl(),
			'site_name' => $helper->getWpOption('blogname'),
			'article:publisher' => $this->getFacebookSite(),
			'image' => $this->getData('og_default_image'),
			'fb:app_id' => $this->getData('fbadminapp'),
		));

		$this->_addGooglePlusLinkRel();
		
		return parent::_beforeObserver();
	}

	/**
	 * Perform global actions after the user_func has been called
	 *
	 * @return $this
	 */	
	protected function _afterObserver()
	{
		$headBlock = $this->_getHeadBlock();
		$helper = Mage::helper('wordpress');
		$robots = array();
			
		if ($this->getNoodp()) {
			$robots[] = 'noodp';
		}
			
		if ($this->getNoydir()) {
			$robots[] = 'noydir';
		}
		
		if (count($robots) > 0) {
			if ($headBlock->getRobots() === '*') {
				$headBlock->setRobots('index,follow,' . implode(',', $robots));
			}
			else {
				$robots = array_unique(array_merge(explode(',', $headBlock->getRobots()), $robots));

				$headBlock->setRobots(implode(',', $robots));
			}
		}

#		$this->_updateBreadcrumb('blog', $this->getBreadcrumbsHome());

		/**
		 * Open Graph Tags
		 */
		if ((int)$this->getData('opengraph') === 1) {
			$tagString = '';

			foreach($this->_openGraphTags as $key => $value) {
				$tkey = strpos($key, ':') === false ? 'og:' . $key : $key;
				
				if (!is_array($value)) {
					$value = array($value);
				}
				
				foreach($value as $v) {
					if (trim($v) !== '') {
						$tagString .= sprintf('<meta property="%s" content="%s" />', $tkey, addslashes($helper->escapeHtml($v))) . "\n";
					}
				}
			}
	
			$headBlock->setChild('wp.openGraph', 
				Mage::getSingleton('core/layout')->createBlock('core/text')->setText($tagString . "\n")
			);
		}
		
		return $this;
	}

	/**
	 * Process the SEO values for the homepage
	 *
	 * @param $action
	 * @param Varien_Object $object
	 */	
	public function processRouteWordPressIndexIndex($object = null)
	{
		if (is_object($object) && $object instanceof Fishpig_Wordpress_Model_Post) {
			if ($object->isBlogListingPage()) {
				return $this->processRouteWordPressPageView($object);
			}
		}
		
		$this->_applyMeta(array(
			'title' => $this->getTitleHomeWpseo(),
			'description' => trim($this->getMetadescHomeWpseo()),
			'keywords' => trim($this->getMetakeyHomeWpseo()),
		));

		$this->_applyOpenGraph(array(
			'title' => $this->getData('og_frontpage_title'),
			'description' => $this->getData('og_frontpage_desc'),
			'image' => $this->getData('og_frontpage_image'),
		));
		
		$this->_addRelNextPrev($object, __METHOD__);
		
		return $this;
	}

	/**
	 * Process the SEO values for the blog view page
	 *
	 * @param $action
	 * @param Varien_Object $post
	 */	
	public function processRouteWordPressPostView($post)	
	{
		$this->_applyPostPageLogic($post);
		
		return $this;
	}
	
	/**
	 * Process the SEO values for the blog view page
	 *
	 * @param $action
	 * @param Varien_Object $page
	 */	
	public function processRouteWordPressPageView($page)	
	{
		$this->_applyPostPageLogic($page, 'page');
				
		if ((Mage::helper('wordpress')->isAddonInstalled('Root') && Mage::getStoreConfig('wordpress/integration/at_root'))) {
			$this->getAction()->removeCrumb('blog');
		}

		return $this;
	}
	
	/**
	 * Process the SEO values for the blog view page
	 *
	 * @param Varien_Object $object
	 *  @param string $type
	 * @param Varien_Object $page
	 */	
	protected function _applyPostPageLogic($object, $type = 'post')
	{
		if ($object->isBlogListingPage()) {
			$this->_addRelNextPrev($object, __METHOD__);
		}
		
		$meta = new Varien_Object(array(
			'title' => $this->_getTitleFormat($object->getPostType()),
			'description' => trim($this->getData('metadesc_' . $object->getPostType())),
			'keywords' => trim($this->getData('metakey_' . $object->getPostType())),
		));

		if (($value = trim($object->getMetaValue('_yoast_wpseo_title'))) !== '') {
			$data = $this->getRewriteData();
			$data['title'] = $value;
			$this->setRewriteData($data);
		}
		
		if (($value = trim($object->getMetaValue('_yoast_wpseo_metadesc'))) !== '') {
			$meta->setDescription($value);
		}
		
		if (($value = trim($object->getMetaValue('_yoast_wpseo_metakeywords'))) !== '') {
			$meta->setKeywords($value);
		}
		
		$robots = array();

		$noIndex = (int)$object->getMetaValue('_yoast_wpseo_meta-robots-noindex');

		if ($noIndex === 0) {
			$robots['index'] = '';
		}
		else if ($noIndex === 1) {
			$robots['noindex'] = '';
		}
		else if ($noIndex === 2) {
			$robots['index'] = '';
		}
		else if ($this->getNoindexPost()) {
			$robots['noindex'] = '';
		}
		
		if ($object->getMetaValue('_yoast_wpseo_meta-robots-nofollow')) {
			$robots['nofollow'] = '';
		}
		else {
			$robots['follow'] = '';
		}

		if (($advancedRobots = trim($object->getMetaValue('_yoast_wpseo_meta-robots-adv'))) !== '') {
			if ($advancedRobots !== 'none') {
				$robots = explode(',', $advancedRobots);
			}
		}

		/* Allow custom fields in meta data */
		$data = $this->getRewriteData();
		
		foreach($meta->getData() as $key => $value) {
			if (strpos($value, '%%cf_') !== false) {
				if (preg_match_all('/%%cf_([^%]+)%%/', $value, $matches)) {
					foreach($matches[1] as $customField) {
						$data['cf_' . $customField] = $object->getMetaValue($customField);
					}
				}
			}
		}
		
		$this->setRewriteData($data);

		$robots = array_keys($robots);

		if (count($robots) > 0) {
			$meta->setRobots(implode(',', $robots));
		}

		if (!$meta->getDescription()) {
			$meta->setDescription($object->getMetaDescription());
		}
		
		$this->_applyMeta($meta->getData());

		if ($canon = $object->getMetaValue('_yoast_wpseo_canonical')) {
			$object->setCanonicalUrl($canon);
		}
		
		if (!$this->hasOpengraph() || (int)$this->getOpengraph() ===1) {
			$this->_addPostOpenGraphTags($object, $object->getPostType());
		}
		
		if ($this->getTwitter()) {
			$twitterData = array(
				'card' => $this->getTwitterCardType(),
				'site' => ($this->getTwitterSite() ? '@' . $this->getTwitterSite() : ''),
				'creator' => ($creator = $object->getAuthor()->getMetaValue('twitter')) ? '@' . $creator : '',
				'title' => $object->getMetaValue('_yoast_wpseo_twitter-title'),
				'description' => $object->getMetaValue('_yoast_wpseo_twitter-description'),
				'image' => $object->getMetaValue('_yoast_wpseo_twitter-image'),
			);

			if (!$twitterData['title']) {
				$twitterData['title'] = $object->getPostTitle();
			}
			
			if (!$twitterData['image']) {
				$twitterData['image'] = $object->getFeaturedImage() ? $object->getFeaturedImage()->getFullSizeImage() : null;
			}

			$this->_addTwitterCard($twitterData);
		}
		
		return $this;
	}

	/**
	 * Category page
	 *
	 * @param Varien_Object $category
	 */
	public function processRouteWordpressTermView($term)
	{
		$this->_addRelNextPrev($object, __METHOD__);

		$this->_applyMeta(array(
			'title' => $this->getData('title_tax_' . $term->getTaxonomyType()),
			'description' => $this->getData('metadesc_tax_' . $term->getTaxonomyType()),
			'keywords' => $this->getData('metakey_tax_' . $term->getTaxonomyType()),
			'robots' => $this->getData('noindex_tax_' . $term->getTaxonomyType()) ? 'noindex,follow' : '',
		));
		
		$this->_applyOpenGraph(array(
			'type' => 'object',
			'title' => $term->getName(),
			'url' => $term->getUrl(),
			'description' => $term->getDescription(),
		));

		if ($meta = @unserialize(Mage::helper('wordpress')->getWpOption('wpseo_taxonomy_meta'))) {
			if (isset($meta[$term->getTaxonomyType()]) && isset($meta[$term->getTaxonomyType()][$term->getId()])) {
				$meta = new Varien_Object((array)$meta[$term->getTaxonomyType()][$term->getId()]);

				$this->_applyMeta(array(
					'title' => $meta->getWpseoTitle(),
					'description' => $meta->getWpseoDesc(),
					'keywords' => $meta->getWpseoMetakey(),
				));
				
				if ($meta->getWpseoCanonical()) {
					$term->setCanonicalUrl($meta->getWpseoCanonical());
				}

				$this->_applyOpenGraph(array(
					'title' => $meta->getWpseoTitle(),
					'description' => $meta->getWpseoDesc(),
					'url' => $term->getCanonicalUrl(),
				));
			}
		}
		
		return $this;
	}

	/**
	 * Archive page
	 *
	 * @param Varien_Object $archive
	 */
	public function processRouteWordpressArchiveView($archive)
	{
		if ($this->getDisableDate()) {
			$this->_redirect(Mage::helper('wordpress')->getBlogRoute());
		}

		$this->_addRelNextPrev($object, __METHOD__);
		
		$meta = new Varien_Object(array(
			'title' => $this->getTitleArchiveWpseo(),
			'description' => $this->getMetadescArchiveWpseo(),
			'keywords' => $this->getMetakeyArchiveWpseo(),
			'robots' => $this->getNoindexArchiveWpseo() ? 'noindex,follow' : '',
		));

		$this->_applyMeta($meta->getData());
		
		$this->_applyOpenGraph(array(
			'type' => 'object',
			'title' => $this->_rewriteString($this->getTitleArchiveWpseo()),
			'url' => $archive->getUrl(),
		));
		
		$this->_updateBreadcrumb('archive_label', $this->getBreadcrumbsArchiveprefix());
		
		return $this;
	}
	
	/**
	 * Author page
	 *
	 * @param Varien_Object $author
	 */
	public function processRouteWordpressAuthorView($author)
	{
		if ($this->getDisableAuthor()) {
			$this->_redirect(Mage::helper('wordpress')->getBlogRoute());
		}

		$this->_addRelNextPrev($object, __METHOD__);
		
		$meta = new Varien_Object(array(
			'title' => $this->getTitleAuthorWpseo(),
			'description' => $this->getMetadescAuthorWpseo(),
			'keywords' => $this->getMetakeyAuthorWpseo(),
			'robots' => $this->getNoindexAuthorWpseo() ? 'noindex,follow' : '',
		));

		$this->_applyMeta($meta->getData());
		
		$this->_applyOpenGraph(array(
			'type' => 'object',
			'title' => $this->_rewriteString($this->getTitleAuthorWpseo()),
			'url' => $author->getUrl(),
		));
			
		return $this;
	}
	
	/**
	 * Process the search results page
	 *
	 * @param $object
	 */
	public function processRouteWordpressSearchIndex($object = null)
	{
		$this->_addRelNextPrev($object, __METHOD__);
		
		$meta = new Varien_Object(array(
			'title' => $this->getTitleSearchWpseo(),
		));

		$this->_applyMeta($meta->getData());

		$this->_applyOpenGraph(array(
			'type' => 'object',
			'title' => $this->_rewriteString($this->getTitleSearchWpseo()),
			'url' => Mage::getUrl('*/*/*', array('_current' => true, '_use_rewrite' => true)),
		));
		
		$this->_updateBreadcrumb('search_label', $this->getBreadcrumbsSearchprefix());
		
		return $this;		
	}

	/**
	 * Process the custom post type archive page
	 *
	 * @param $object
	 */
	public function processRouteWpAddonCptIndexView($object)
	{
		$this->_applyMeta(array(
			'title' => $this->_getTitleFormat('ptarchive_' .$object->getPostType()),
			'description' => trim($this->getData('metadesc_ptarchive_' . $object->getPostType())),
		));
		
		if (Mage::helper('wordpress')->isAddonInstalled('Root')) {
			if (Mage::getStoreConfigFlag('wordpress/integration/at_root')) {
				$this->getAction()->removeCrumb('blog');
			}
		}
		
		return $this;
	}
		
	/**
	 * Add the Google Plus rel="author" tag
	 *
	 * @return $this
	 */
	protected function _addGooglePlusLinkRel()
	{
		$user = Mage::registry('wordpress_author');
		$post = Mage::registry('wordpress_post');

		if (is_null($user) && !is_null($post)) {
			$user = $post->getAuthor();
		}

		if (!is_null($user)) {
			if ($user->getMetaValue('googleplus')) {
				$this->_getHeadBlock()->addItem('link_rel', $user->getMetaValue('googleplus'), 'rel="author"');
			}
		}

		if ($publisher = $this->getData('plus_publisher')) {
			$this->_getHeadBlock()->addItem('link_rel', $publisher, 'rel="publisher"');
		}

		return $this;	
	}
	
	/**
	 * Add a Twitter card to the head
	 *
	 * @param array $tafs
	 * @return $this
	 */
	protected function _addTwitterCard(array $tags)
	{
		if (($head = Mage::getSingleton('core/layout')->getBlock('head')) !== false) {
			$helper = Mage::helper('wordpress');

			foreach($tags as $key => $value) {
				if (trim($value) !== '') {
					$tags[$key] = sprintf('<meta name="twitter:%s" content="%s" />', $key, addslashes($helper->escapeHtml($value)));
				}
				else {
					unset($tags[$key]);
				}
			}

			$head->setChild('wp.twitterCard', 
				Mage::getSingleton('core/layout')->createBlock('core/text')->setText(implode("\n", $tags) . "\n")
			);
		}

		return $this;
	}

	/**
	 * Add the OG tags ready for applying
	 *
	 * @param array $tags
	 * @return $this
	 */
	protected function _applyOpenGraph(array $tags)
	{
		foreach($tags as $tag => $value) {
			if (!is_array($value) && trim($value) === '') {
				continue;
			}
			
			$this->_openGraphTags[$tag] = $value;
		}
		
		return $this;
	}
	
	/**
	 * Add the open graph tags to the post/page
	 *
	 * @object
	 * @param string $type = 'post'
	 * @return
	 */
	protected function _addPostOpenGraphTags($object, $type = 'post')
	{
		$tags = array(
			'type' => 'article',
			'title' => $object->getPostTitle(),
			'description' => $object->getMetaDescription(),
			'url' => $object->getPermalink(),
			'image' => $object->getFeaturedImage() ? $object->getFeaturedImage()->getFullSizeImage() : '',
			'updated_time' => $object->getPostModifiedDate('c'),
			'article:author' => $object->getAuthor()->getMetaValue('facebook'),
			'article:published_time' => $object->getPostDate('c'),
			'article:modified_time' => $object->getPostModifiedDate('c'),
		);

		if ($fbTitle = $object->getMetaValue('_yoast_wpseo_opengraph-title')) {
			$tags['title'] = $fbTitle;
		}

		if ($fbDesc = $object->getMetaValue('_yoast_wpseo_opengraph-description')) {
			$tags['description'] = $fbDesc;
		}
		else if (!$tags['description']) {
			if ($head = Mage::getSingleton('core/layout')->getBlock('head')) {
				$tags['description'] = $head->getDescription();
			}
		}
		
		if ($fbImage = $object->getMetaValue('_yoast_wpseo_opengraph-image')) {
			$tags['image'] = $fbImage;
		}

		if ($items = $object->getTags()) {
			$tagValue = array();

			foreach($items as $item) {
				$tagValue[] = $item->getName();
			}
			
			$tags['article:tag'] = $tagValue;
		}
		
		if ($items = $object->getParentCategories()) {
			$categoryValue = array();

			foreach($items as $item) {
				$categoryValue[] = $item->getName();
			}
			
			$tags['article:section'] = $categoryValue;
		}

		return $this->_applyOpenGraph($tags);
	}

	/**
	 * Retrieve the rewrite data
	 *
	 * @return array
	 */
	public function getRewriteData()
	{
		if (!$this->hasRewriteData()) {
			$data = array(
				'sitename' => Mage::helper('wordpress')->getWpOption('blogname'),
				'sitedesc' => Mage::helper('wordpress')->getWpOption('blogdescription'),
			);
			
			if (($object = Mage::registry('wordpress_post')) !== null || ($object = Mage::registry('wordpress_page')) !== null) {
				$data['date'] = $object->getPostDate();
				$data['title'] = $object->getPostTitle();
				$data['excerpt'] = trim(strip_tags($object->getPostExcerpt()));
				$data['excerpt_only'] = $data['excerpt'];
				
				$categories = array();
				
				if ($object instanceof Fishpig_Wordpress_Model_Post) {
					foreach($object->getParentCategories()->load() as $category) {
						$categories[] = $category->getName();	
					}
				}
				
				$data['category'] = implode(', ', $categories);
				$data['modified'] = $object->getPostModified();
				$data['id'] = $object->getId();
				$data['name'] = $object->getAuthor()->getUserNicename();
				$data['userid'] = $object->getAuthor()->getId();
			}
			
			if (($term = Mage::registry('wordpress_term')) !== null) {
				$data['term_description'] = trim(strip_tags($term->getDescription()));
				$data['term_title'] = $term->getName();
			}
			
			if (($archive = Mage::registry('wordpress_archive')) !== null) {
				$data['date'] = $archive->getName();
			}
			
			if (($author = Mage::registry('wordpress_author')) !== null) {
				$data['name'] = $author->getDisplayName();
			}			
			
			if (($postType = Mage::registry('wordpress_post_type')) !== null) {
				$data['pt_single'] = $postType->getNameSingular();
				$data['pt_plural'] = $postType->getName();
			}

			$data['currenttime'] = Mage::helper('wordpress')->formatTime(date('Y-m-d H:i:s'));
			$data['currentdate'] = Mage::helper('wordpress')->formatDate(date('Y-m-d H:i:s'));
			$data['currentmonth'] = date('F');
			$data['currentyear'] = date('Y');
			$data['sep'] = '|';

			if ($sep = $this->getData('separator')) {
				if (isset($this->_separatorOptions[$sep])) {
					$data['sep'] = $this->_separatorOptions[$sep];
				}
			}

			if (($value = trim(Mage::helper('wordpress/router')->getSearchTerm(true))) !== '') {
				$data['searchphrase'] = $value;
			}
			
			if (($page = (int)Mage::app()->getRequest()->getParam('page')) > 1) {
				$data['page'] = $data['sep'] . ' Page ' . $page;
			}
			
			$this->setRewriteData($data);
		}
		
		return $this->_getData('rewrite_data');		
	}
	
	/**
	 * Retrieve the title format for the given key
	 *
	 * @param string $key
	 * @return string
	 */
	protected function _getTitleFormat($key)
	{
		return trim($this->getData('title_' . $key));
	}
			
	/**
	 * Given a key that determines which format to load
	 * and a data array, merge the 2 to create a valid title
	 *
	 * @param string $key
	 * @return string|false
	 */
	protected function _rewriteStringg($format)
	{
		if (($value = parent::_rewriteString($format)) !== false) {
			$data = $this->getRewriteData();

			if (is_array($data) && isset($data['sep'])) {
				$value = trim($value, $data['sep'] . ' -/\|,');
			}
		}
		
		return $value;
	}
	
	/**
	 * Determine whether to remove the category base
	 *
	 * @return string
	 */
	public function canRemoveCategoryBase()
	{
		return $this->isEnabled() && (int)$this->getData('stripcategorybase') === 1;
	}

	/**
	 * Process the SEO values for the Tribe Events homepage
	 *
	 * @param $action
	 * @param Varien_Object $post
	 */	
	public function processRouteWpAddonEventscalendarIndexIndex($object)
	{
		return $this;
	}
	
	/**
	 * Process the SEO values for the Tribe Events view page
	 *
	 * @param $action
	 * @param Varien_Object $post
	 */	
	public function processRouteWpAddonEventscalendarEventView($post)
	{
		$this->_applyPostPageLogic($post, $post->getPostType());
		
		return $this;
	}
	
	/**
	 * Tribe Events category page
	 *
	 * @param Varien_Object $category
	 */
	public function processRouteWpAddonEventscalendarEventCategoryView($term)
	{
		return $this->processRouteWordpressTermView($term);
	}
	
	/**
	 * Get the primary category for $post
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return false|Fishpig_Wordpress_Model_Term
	 **/
	public function getPostPrimaryCategory(Fishpig_Wordpress_Model_Post $post)
	{
		if (!$this->isEnabled()) {
			return false;
		}

		if ($categoryId = $post->getMetaValue('_yoast_wpseo_primary_category')) {
			$category = Mage::getModel('wordpress/term')->setTaxonomy('category')->load($categoryId);
			
			if ($category->getId()) {
				return $category;
			}
		}
		
		return false;
	}
	
	/**
	 * Add the primary category to the select object
	 *
	 * @param 
	 * @param 
	 * @return $this
	 **/
	public function addPrimaryCategoryToSelect($select, $post)
	{
		if (!$this->isEnabled()) {
			return $this;
		}
		
		if (is_object($post)) {
			$post = $post->getId();
		}

		$tempPostModel = Mage::getModel('wordpress/post')->setId($post);

		if ($categoryId = $tempPostModel->getMetaValue('_yoast_wpseo_primary_category')) {
			$select->reset(Zend_Db_Select::ORDER)->where('_term.term_id=?', $categoryId);
		}
		
		return $this;
	}

	/**
	 * Ensure post types are correctly converted
	 *
	 * @param string $key
	 * @param string $index
	 * @return mixed
	**/
	public function getData($key='', $index=null)
	{
		return parent::getData(str_replace('-', '_', $key), $index);
	}
	
	/*
	 *
	 *
	 * @param  $object
	 * @param  string $method
	 * @return
	 */
	protected function _addRelNextPrev($object, $method)
	{
		return $this;

		$postListBlock = Mage::getSingleton('core/layout')->getBlock('wordpress_post_list');
		
		if (!$postListBlock) {
			return false;
		}
		
		$postListWrapperBlock = $postListBlock->getParentBlock();
		
		// This ensure it is the correct post list block
		if (!$postListWrapperBlock || !($postListWrapperBlock instanceof Fishpig_Wordpress_Block_Post_List_Wrapper_Abstract)) {
			return false;
		}
		
		$postCollection = $postListBlock->getPosts();
	}
}
