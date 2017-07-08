<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
	/**
	 * If Legacy add-on extension installed
	 * Apply legacy hacks
	 *
	 * @param string $resourceName
	 * @return void
	 */
	public function __construct($resourceName)
	{
		if (Mage::helper('wordpress')->isLegacy()) {
			if ($helper = Mage::helper('wp_addon_legacy')) {
				$helper->applyLegacyHacks();
			}
		}
		
		parent::__construct($resourceName);
	}
}
