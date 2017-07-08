<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

abstract class Fishpig_Wordpress_Block_Abstract extends Mage_Core_Block_Template
{
	/**
	 * Allows for legacy Magento
	 *
	 * @param string $data
	 * @param array $allowedTags = null
	 * @return string
	 */
	public function escapeHtml($data, $allowedTags = null)
	{
		return Mage::helper('wordpress')->escapeHtml($data, $allowedTags);
	}
}
