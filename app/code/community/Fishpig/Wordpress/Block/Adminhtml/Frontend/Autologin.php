<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Block_Adminhtml_Frontend_Autologin extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	 * Singleton flag
	 *
	 * @var bool
	 */
	static $_singleton = false;

	/**
	 * If cannot display fields, display error message
	 *
	 * @param Varien_Data_Form_Element_Abstract $element
	 * @return string|false
	 */
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		$version = Mage::getVersion();
		
		if (version_compare($version, '1.5.9.9', '<') && version_compare($version, '1.3.0.0', '>')) {
			if (self::$_singleton === false) {
				self::$_singleton = true;

				return '<tr>
					<td class="comment" colspan="3"><em>This feature is not supported with your version of Magento.</em></td>
				</tr>';
			}
			
			return false;
		}
		
		return parent::render($element);
	}
}
