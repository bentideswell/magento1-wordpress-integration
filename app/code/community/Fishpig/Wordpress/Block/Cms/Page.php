<?php
/*
 *
 */
class Fishpig_Wordpress_Block_Cms_Page extends Mage_Cms_Block_Page
{
	/*
	 * Render shortcodes in CMS content
	 *
	 * @return string
	 */
	protected function _toHtml()
	{
		$html = parent::_toHtml();
		
		if (strpos($html, '[') !== false && strpos($html, ']') !== false) {
			$html = Mage::helper('wordpress/filter')->doShortcode($html);
		}
		
		return $html;
	}
}