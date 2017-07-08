<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Social extends Fishpig_Wordpress_Helper_Abstract
{
	/**
	 * Service alias
	 *
	 * @const string
	 */
	const SERVICE_SHARETHIS = 'sharethis';
		
	/**
	 * Determine whether the social functionality is enabled
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return Mage::getStoreConfigFlag('wordpress/social/enabled');
	}
	
	/**
	 * Get the selected service
	 *
	 * @return bool|string
	 */
	public function getService()
	{
		if (!$this->isEnabled()) {
			return false;
		}
		
		$service = trim(Mage::getStoreConfig('wordpress/social/service'));
		
		return $service !== ''
			? $service
			: false;
	}
	
	/**
	 * Determine whether the selected service is ShareThis
	 *
	 * @return bool
	 */
	public function isShareThis()
	{
		return $this->getService() === self::SERVICE_SHARETHIS;
	}
	
	/**
	 * Add the required JS/CSS to the head of the page
	 *
	 * @return $this
	 */
	public function addCodeToHead()
	{
		if ($this->isShareThis()) {
			$_layout = Mage::getSingleton('core/layout');
			
			$_layout->getBlock('head')->append(
				$_layout->createBlock('core/text')
					->setText(
						$this->_getHeadHtml()
					)
			);
		}
		
		return $this;
	}
	
	/**
	 * Get the buttons HTML for the given $post and selected service
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return string
	 */
	public function getButtons(Fishpig_Wordpress_Model_Post $post)
	{
		if ($this->isShareThis()) {
			$buttonsHtml = $this->_getButtonsHtml();
			
			if (preg_match_all('/(<span.*)(>.*<\/span>)/Us', $buttonsHtml, $matches)) {
				foreach($matches[1] as $it => $prefix) {
					$suffix = $matches[2][$it];

					$middle = sprintf(' st_title="%s"', $this->_sanitizeString($post->getPostTitle()));
					$middle .= sprintf(' st_url="%s"', $post->getPermalink());
					
					if ($featuredImage = $post->getFeaturedImage()) {
						$middle .= sprintf(' st_image="%s"', $featuredImage->getAvailableImage());
					}

					if (($excerpt = trim($post->getMetaValue('_yoast_wpseo_metadesc'))) !== '') {
						$middle .= sprintf(' st_summary="%s"', $this->_sanitizeString($excerpt, '<a><span><strong><em>'));						
					}
					else if (($excerpt = trim($post->getData('post_excerpt'))) !== '') {
						$middle .= sprintf(' st_summary="%s"', $this->_sanitizeString($excerpt, '<a><span><strong><em>'));
					}
					else {
						$middle .= sprintf(' st_summary="%s"', $this->_sanitizeString($post->getPostExcerpt(20), '<a><span><strong><em>'));						
					}

					$buttonsHtml = str_replace($matches[0][$it], $prefix . $middle . $suffix, $buttonsHtml);
				}

				return $buttonsHtml;
			}
		}

		return '';
	}
	
	/**
	 * Get the HTML required for the head of the page
	 * This is loaded from the Magento configuration
	 *
	 * @return string
	 */
	protected function _getHeadHtml()
	{
		return Mage::getStoreConfig('wordpress/social/head_html');
	}
	
	/**
	 * Get the raw buttons HTML provided by the social share service
	 * This is loaded from the Magento configuration
	 *
	 * @return string
	 */
	protected function _getButtonsHtml()
	{
		return Mage::getStoreConfig('wordpress/social/buttons_html');
	}
	
	/**
	 * Sanitize a string so it becomes a valid HTML element parameter value
	 *
	 * @param string $s
	 * @return string
	 */
	protected function _sanitizeString($s)
	{
		return addcslashes(
			trim(strip_tags($s)),
			'"'
		);
	}
}
