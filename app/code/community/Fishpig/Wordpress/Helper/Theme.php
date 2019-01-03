<?php
/*
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
class Fishpig_Wordpress_Helper_Theme extends Fishpig_Wordpress_Helper_Abstract
{
	/*
	 * Theme name
	 *
	 * @const string
	 */
	const THEME_NAME = 'fishpig';

	/*
	 * Get the theme directory
	 *
	 * @return string
	 */
	public function getThemeDirectory()
	{
		if (!($wpPath = Mage::helper('wordpress')->getWordPressPath())) {
			throw new Exception('WordPress path not set. Cannot install theme.');
		}
		
		return $wpPath . 'wp-content' . DS . 'themes' . DS . self::THEME_NAME . DS;
	}

	/*
	 * Get the source directory
	 *
	 * @return string
	 */
	public function getSourceDirectory()
	{
		return Mage::getModuleDir('', 'Fishpig_Wordpress') . DS . 'wptheme' . DS;
	}
	
	/**
	 * Install the theme
	 * 
	 * @return bool
	 */
	public function install()
	{
		$targetDir     = $this->getThemeDirectory();
		$sourceDir     = $this->getSourceDirectory();
		$sourceCssFile = $sourceDir . '/style.css';
		$targetCssFile = $targetDir . '/style.css';

		if (is_file($targetCssFile) && md5_file($sourceCssFile) === md5_file($targetCssFile)) {
			return true;
		}

		if (!is_dir($sourceDir)) {
			throw new Exception('Theme source directory does not exist at ' . $sourceDir);
		}

		if (!is_dir($targetDir)) {
			@mkdir($targetDir, 0755, true);

			if (!is_dir($targetDir)) {
				throw new Exception('Unable to create theme directory at ' . $targetDir);
			}
		}
			
		if ($files = scandir($sourceDir)) {
			array_shift($files);
			array_shift($files);

			foreach($files as $file) {
				if ($file !== 'local.php') {
					@copy($sourceDir . $file, $targetDir . $file);
				}
			}
		}

		return is_file($targetCssFile);
	}
	
	/**
	 * Determine whether a the WordPress theme is enabled
	 *
	 * @return bool
	 */
	public function isEnabled()
	{
		return Mage::helper('wordpress')->getWpOption('template') === self::THEME_NAME;
	}
	
	/*
	 * Enable the FishPig theme in WordPress
	 *
	 * @return $this
	 */
	public function enable()
	{
		Mage::helper('wordpress')->setWpOption('template',   self::THEME_NAME);
		Mage::helper('wordpress')->setWpOption('stylesheet', self::THEME_NAME);
		
		return $this;
	}

	/*
	 * Get the version from the file $f
	 *
	 * @param string $file
	 * @return string|false
	 */
	protected function _getVersion($f)
	{
		if (is_file($f)) {
			if (preg_match('/Version:[\s]*([0-9\.]+)\n/', @file_get_contents($f), $m)) {
				return $m[1];
			}
		}
		
		return false;
	}
}
