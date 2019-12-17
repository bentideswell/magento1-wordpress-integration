<?php
/*
 *
 */
class Fishpig_Wordpress_Block_Shortcode extends Mage_Core_Block_Abstract
{
	/*
	 * Render html output
	 *
	 * @return string
	 */
	protected function _toHtml()
	{
		if (!$this->_beforeToHtml()) {
		  return '';
		}

		if (!($shortcode = $this->getShortcode())) {
			return '';
		}

		return Mage::helper('wordpress/filter')->doShortcode($shortcode);
	}
	
	/*
	 *
	 *
	 * @return string
	 */
	public function getShortcode()
	{
		return str_replace("\\\"", '"', $this->getData('shortcode'));
	}
}
