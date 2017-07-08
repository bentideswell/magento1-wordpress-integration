<?php
/**
 *
**/

/**
 * Dirty hack to fix conflict.
 */
$moduleDir = Mage::getModuleDir('etc', 'Hackathon_LocaleFallback');

if (is_dir($moduleDir)) {
	class Fishpig_Wordpress_Model_Translate_Hack extends Hackathon_LocaleFallback_Model_Translate {}	
}
else {
	class Fishpig_Wordpress_Model_Translate_Hack extends Mage_Core_Model_Translate {}
}
/**
 * End of dirty hack to fix conflict.
 */
 
class Fishpig_Wordpress_Model_Translate extends Fishpig_Wordpress_Model_Translate_Hack
{
	/**
	 * @var bool
	**/
	static protected $_isSimulationActive = false;
	
	/**
	 * WordPress translate strings using the following:
	 * __( 'String to translate', 'plugin_name')
	 * If the String to translate contains a %s placeholder, the Magento translation function
	 * will replace it for the plugin name, which is false
	 * This method checks for that situation and unsets the plugin name argument
	 * If it's not currently being called by WP, the parent method is called
	 *
	 * @array $args
	 * @return string
	**/
	public function translate($args)
	{
		// If not simulating WordPress, return the parent translate method
		if (!$this->isSimulationActive()) {
			return parent::translate($args);
		}
		
		// No args passed so let the parent handle the error
		if (!$args) {
			return parent::translate($args);
		}
		
		// Copy the args for calling the parent later
		$buffer = $args;
		
		// Get first arg. Should be text to translate
        $text = array_shift($buffer);
		
		if (is_object($text)) {
			$text = (string)$text;
		}

		// Text doesn't contain %s so call parent method
		if (strpos($text, '%s') === false) {
			return parent::translate($args);
		}
		
		// Translate the text without the arg
		// As we are in WordPress, the arg will be the plugin name
		return parent::translate(array($text));
	}
	
	/*
	 * Determine - or set - whether WP is in simulation mode
	 *
	 * @param bool|null $flag = null
	 * @return $this|bool
	**/
	public function isSimulationActive($flag = null)
	{
		if (null === $flag) {
			return self::$_isSimulationActive;
		}
		
		self::$_isSimulationActive = (bool)$flag;
		
		return $this;
	}
}