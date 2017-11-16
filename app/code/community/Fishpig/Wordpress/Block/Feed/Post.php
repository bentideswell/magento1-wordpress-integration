<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Feed_Post extends Fishpig_Wordpress_Block_Feed_Abstract
{
	/**
	 * Generate the entries and add them to the RSS feed
	 *
	 * @param Zend_Feed_Writer_Feed $feed
	 * @return $this
	 */
	protected function _addEntriesToFeed($feed)
	{
		$this->getItemAfterContent();
			
		$posts = Mage::getSingleton('core/layout')->createBlock($this->getSourceBlock())
			->getPostCollection();

		$this->_prepareItemCollection($posts);

		foreach($posts as $post) {
			$entry = $feed->createEntry();

			if (!$post->getPostTitle()) {
				continue;
			}

			if (!($postDate = strtotime($post->getData('post_date_gmt')))) {
				continue;
			}

			$entry->setDateModified($postDate);
			
			$entry->setTitle($post->getPostTitle());
			$entry->setLink($post->getPermalink());

			$_author = $post->getAuthor();
			
			if ($_author->getUserEmail() && $_author->getDisplayName()) {
				$entry->addAuthor(array(
					'name' => $_author->getDisplayName(),
					'email' => $_author->getUserEmail(),
				));
			}
			
			$description = $this->_applyVars(Mage::helper('wp_addon_wordpressseo')->getData('rssbefore'), $post)
				. ($this->displayExceprt() ? $post->getPostExcerpt() : $post->getPostContent())
				. $this->_applyVars(Mage::helper('wp_addon_wordpressseo')->getData('rssafter'), $post);

			$entry->setDescription($description ? $description : '&nbsp;');
			
			if ($image = $post->getFeaturedImage()) {
				$entry->setDescription($entry->getDescription() . '<p><img src="' . $image->getFullSizeImage() . '" alt=""/></p>');
				
				/*
				$entry->setDescription(
					$entry->getDescription() 
					. "\n\n"
					. sprintf(Fishpig_Wordpress_Block_Feed_Abstract::IMG_WRAPPER, $image->getFullSizeImage(), $post->getId())
				);
				*/
			}
			
			foreach($post->getParentCategories() as $category) {
				$entry->addCategory(array(
					'term' => $category->getUrl(),
				));
			}
			
			$feed->addEntry($entry);
		}
	

		return $this;
	}

	/**
	 * Determine whether to display the excerpt
	 *
	 * @return bool
	 */
	public function displayExceprt()
	{
		return Mage::helper('wordpress')->getWpOption('rss_use_excerpt');
	}

	/**
	 * Apply variables to a string
	 *
	 * @param string $str
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return string
	 */
	protected function _applyVars($str, $post)
	{
		if (trim($str) === '') {
			return '';
		}

		$_helper = Mage::helper('wordpress');

		return str_replace(
			array(
				'%%AUTHORLINK%%',
				'%%POSTLINK%%',
				'%%BLOGLINK%%',
				'%%BLOGDESCLINK%%',
			),
			array(
				$this->_createATag($post->getAuthor()->getUrl(), $post->getAuthor()->getDisplayName()),
				$this->_createATag($post->getPermalink(), $post->getPostTitle()),
				$this->_createATag($_helper->getUrl(), $_helper->getWpOption('blogname')),
				$this->_createATag($_helper->getUrl(), $_helper->getWpOption('blogname') . ' - ' . $_helper->getWpOption('blogdescription')),
			),
			$str
		);
	}
	
	/**
	 * Create an 'A' HTML tag
	 *
	 * @param string $href
	 * @param string $anchor
	 * @return string
	 */
	protected function _createATag($href, $anchor)
	{
		return sprintf('<a href="%s">%s</a>', $href, htmlentities($anchor));
	}
}
