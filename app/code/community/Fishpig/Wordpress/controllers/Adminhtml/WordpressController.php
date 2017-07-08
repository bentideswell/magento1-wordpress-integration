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
	const URL_RELEASES = 'http://connect20.magentocommerce.com/community/Fishpig_Wordpress_Integration/releases.xml';

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
				$xml = simplexml_load_string($response);
				$latest = false;
				
				foreach($xml->r as $release) {
					if ((string)$release->s === 'stable') {
						if (!$latest || version_compare($release->v, $latest, '>=')) {
							$latest = (string)$release->v;
						}
					}
				}
				
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
