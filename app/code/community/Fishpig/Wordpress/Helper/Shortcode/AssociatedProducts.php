<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Shortcode_AssociatedProducts extends Fishpig_Wordpress_Helper_Shortcode_Abstract
{
	/**
	 * Retrieve the shortcode tag
	 *
	 * @return string
	 */
	public function getTag()
	{
		return 'associated-products';
	}
	
	/**
	 * Apply the associated products short code
	 *
	 * @param string &$content
	 * @return void
	 */	
	protected function _apply(&$content)
	{
		if (($shortcodes = $this->_getShortcodes($content)) !== false) {
			foreach($shortcodes as $shortcode) {			
				$params = $shortcode->getParams();
				$template = $params->getTemplate() ? $params->getTemplate() : 'wordpress/post/associated/products.phtml';
				$title = $params->getTitle() ? $params->getTitle() : Mage::helper('catalog')->__('Related Products');
					
				$html = $this->_createBlock('wordpress/post_associated_products')
					->setTemplate($template)
					->setTitle($title)
					->toHtml();

				$content = str_replace($shortcode->getHtml(), $html, $content);
			}
		}
	}
}
