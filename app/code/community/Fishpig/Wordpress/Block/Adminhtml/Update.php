<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Adminhtml_Update extends Mage_Core_Block_Text
{
	/**
	 * Ensure any URL's generated are Adminhtml URL's
	 *
	 * @return string
	 */
	protected function _getUrlModelClass()
	{
		return 'adminhtml/url';
	}

	/**
	 * Ensure the required JS is included
	 *
	 * @return $this
	 */
	protected function _prepareLayout()
	{
		if (($headBlock = $this->getLayout()->getBlock('head')) !== false) {
			$headBlock->addJs('fishpig/wordpress/update.js');
		}		

		return parent::_prepareLayout();
	}
	
	/**
	 * Generate the JS required to load the update routine
	 *
	 * @return $this
	 */
	protected function _beforeToHtml()
	{
		if (Mage::getStoreConfigFlag('wordpress/module/check_for_updates')) {
			$current = str_replace('.', '_', Mage::helper('wordpress/system')->getExtensionVersion());
	
			$text = implode('', array(
				'<script type="text/javascript">',
					sprintf("var WP_VERSION_LATEST = '%s';", $latestVersion = Mage::app()->getCache()->load('wordpress_integration_update' . $current)),
					sprintf("var WP_VERSION_CURRENT = '%s';", Mage::helper('wordpress/system')->getExtensionVersion()),
					sprintf("var WP_VERSION_LOOKUP_URL = '%s';", $this->getUrl('adminhtml/wordpress/checkVersion')),
					'new fishpig.WP.Update();',
				'</script>',
			));
			
			$this->setText($text);
		}
		
		return parent::_beforeToHtml();
	}
}
