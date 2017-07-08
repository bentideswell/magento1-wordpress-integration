<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Controller_Router_Addon extends Mage_Core_Controller_Varien_Router_Standard
{
	/**
	 * Fix the controller file name for addon extensions
	 *
	 * @param string $realModule
	 * @param string $controller
	 * @return string
	 */
	public function getControllerFileName($realModule, $controller)
	{
		return Mage::getModuleDir('controllers', $realModule) .DS.uc_words($controller, DS).'Controller.php';
	}
}
