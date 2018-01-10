<?php
/**
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
		$themeDirectory = $this->getThemeDirectory();
		$sourceDirectory = $this->getSourceDirectory();
		
		if (!is_dir($themeDirectory)) {
			if (!$this->_installTheme()) {
				throw new Exception('Unable to create the theme in WordPress.');
			}
		}
		
		if (!is_file($themeDirectory . 'style.css')) {
			$this->_installThemeFiles();
		}
		else if (version_compare($this->_getVersion($sourceDirectory . 'style.css'), $this->_getVersion($themeDirectory . 'style.css'), '>')) {
			$this->_installThemeFiles();
		}
		
		return is_dir($themeDirectory);
	}

	/*
	 * install the theme
	 *
	 * @return $this
	 */
	protected function _installTheme()
	{
		$themeDirectory = $this->getThemeDirectory();		
		$sourceDirectory = $this->getSourceDirectory();

		if (!is_dir($sourceDirectory)) {
			throw new Exception('Theme source directory does not exist at ' . $sourceDirectory);
		}

		if (!is_dir($themeDirectory)) {
			@mkdir($themeDirectory, 0755, true);
			
			if (!is_dir($themeDirectory)) {
				throw new Exception('Unable to create theme directory at ' . $themeDirectory);
			}
			
			$this->_installThemeFiles();
		}
		
		return is_dir($themeDirectory);
	}
	
	/*
	 * Copy theme files from source to target
	 *
	 * @return $this
	 */
	protected function _installThemeFiles()
	{
		$themeDirectory = $this->getThemeDirectory();		
		$sourceDirectory = $this->getSourceDirectory();
		
		if ($files = scandir($sourceDirectory)) {
			array_shift($files);
			array_shift($files);

			foreach($files as $file) {
				@copy($sourceDirectory . $file, $themeDirectory . $file);
			}
		}
		
		return $this;
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
