<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Adminhtml_WordpressController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * URL to get release information for extension
	 *
	 * @const string
	 */
	const URL_RELEASES = 'https://raw.githubusercontent.com/bentideswell/magento1-wordpress-integration/master/app/code/community/Fishpig/Wordpress/etc/config.xml';
	
	/**
	 * Attempt to login to the WordPress Admin action
	 *
	 */
	public function loginAction()
	{
		$autoLogin = Mage::getSingleton('wordpress/system_config_backend_autologin');

		$username = $autoLogin->getUsername();
		$password = $autoLogin->getPassword();
		
		try {
			if (!$username || !$password) {
				throw new Exception($this->__('WordPress Admin details not set.'));
			}
			
			Mage::helper('wordpress/system')->loginToWordPress($username, $password, Mage::helper('wordpress')->getAdminUrl());
			exit;

			$this->_redirectUrl(Mage::helper('wordpress')->getAdminUrl('index.php'));
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e);
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			
			$this->_redirect('adminhtml/system_config/edit', array('section' => 'wordpress'));
		}
	}
	
	/**
	 * Check for the latest WordPress versions
	 *
	 */
	public function checkVersionAction()
	{
		$current = Mage::helper('wordpress/system')->getExtensionVersion();
		$cacheKey = 'wordpress_integration_update' . str_replace('.', '_', $current);

		try {

			if (($latest = Mage::app()->getCache()->load($cacheKey)) === false) {

				$response = Mage::helper('wordpress/system')->makeHttpGetRequest(
					self::URL_RELEASES
				);
		
				if (strpos($response, '<?xml') === false) {
					throw new Exception('Invalid response');
				}

				$response = trim(substr($response, strpos($response, '<?xml')));

				$xml = @simplexml_load_string($response);
				$latest = (string)$xml->modules->Fishpig_Wordpress->version;

				Mage::app()->getCache()->save(
					$latest,
					$cacheKey, 
					array('WP_UPDATE'), 
					((60*60)*24)*7
				);
			}

			$this->getResponse()
				->setHeader('Content-Type', 'application/json; charset=UTF-8')
				->setBody(
					json_encode(
						array('latest_version' => $latest)
					)
				);
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e);
		}		
	}

	public function updateAction()
	{
		try {
			if (!Mage::helper('wordpress/system_update')->update()) {
				throw new Exception('Unable to update module.');
			}
			
			Mage::getSingleton('adminhtml/session')->addSuccess(
				$this->__('Magento WordPress Integration updated to version %s.', Mage::helper('wordpress/system_update')->getCurrentVersion())
			);
		}
		catch (Exception $e) {
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}
		
		return $this->_redirect('*/system_config/edit/section/wordpress');
	}
	
	/**
	 * Determine ACL permissions
	 *
	 * @return bool
	 */
	protected function _isAllowed()
	{
		return true;
	}	
}
