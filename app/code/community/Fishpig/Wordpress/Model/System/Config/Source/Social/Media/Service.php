<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_System_Config_Source_Social_Media_Service
{
	public function getOptions()
	{
		return array(
			Fishpig_Wordpress_Helper_Social::SERVICE_SHARETHIS => Mage::helper('wordpress')->__('ShareThis'),
		);
	}
	
	public function toOptionArray()
	{
		$options = array();
		
		foreach($this->getOptions() as $value => $label) {
			$options[] = array(
				'value' => $value,
				'label' => $label,
			);
		}
		
		return $options;
	}
}