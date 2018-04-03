<?php
/*
 *
 */
class Fishpig_Wordpress_Helper_System_Update extends Mage_Core_Helper_Abstract
{
	/*
	 * ZIP file containing latest version of module
	 *
	 * @const string
	 */
	const MODULE_SOURCE = 'https://github.com/bentideswell/magento1-wordpress-integration/archive/master.zip';
	
	/*
	 * Run the auto update process
	 *
	 * @return $this
	 */
	public function update()
	{
		try {
			if ($this->isGit()) {
				throw new Exception('The module is installed using git. To update, run \'git pull\' on the module directory.');
			}

			// Download the ZIP file and return local copy
			$localZipFile = $this->_downloadZipFile();
			
			// Extract local copy of zip file
			$unzippedDir = $this->_extractZipFile($localZipFile);
			
			if (!($fileBuffer = $this->_scanDir($unzippedDir))) {
				throw new Exception('Zip file extracted but no files in directory.');
			}
	
			$files = array();
			$magentoBaseDir = Mage::getBaseDir();
			
			// Loop through files and change to source => target
			foreach($fileBuffer as $file) {
				$files[$file] = $magentoBaseDir . substr($file, strlen($unzippedDir));
			}
			
			// Loop through and check permissions
			$permErrors = array();
			
			foreach($files as $source => $target) {
				if (file_exists($target) && !is_writable($target)) {
					$permErrors[] = substr($target, strlen($magentoBaseDir)+1);
				}
				else {
					$buffer = $target;
					
					while($target && !file_exists($target)) {
						$target = dirname($target);
					}
					
					if ($target && !is_writable($target)) {
						$permErrors[] = substr($target, strlen($magentoBaseDir)+1);
					}
				}
			}
			
			if (count($permErrors) > 0) {
				throw new Exception('The following files/directories are not writable: ' . implode(',', array_unique($permErrors)));
			}
	
			// Check for symlinks. Fail if found
			foreach($files as $source => $target) {		
				if (is_file($target) && $target !== realpath($target)) {
					throw new Exception('Symlinks found. Cannot auto update module while using symlinks. Update module using git or manually using FTP.');
				}
			}
			
			// Copy over files
			foreach($files as $source => $target) {
				if (is_dir($source)) {
					if (!file_exists($target)) {
						mkdir($target);
					}
				}
				else if (is_file($source)) {
					if (!is_file($target) || md5_file($source) !== md5_file($target)) {
						if (is_dir($target)) {
							Mage::helper('wordpress')->log('Update Error: ' . $target . ' is a directory but ' . $source . ' is a file.');
						}
						
						@copy($source, $target);
					}
				}
			}
	
			// Clean up Zip file
			@unlink($localZipFile);
	
			// Clean up unzipped directory
			$this->_removeDir($unzippedDir);
			
			// Remove the var/cache directory
			$this->_removeDir(Mage::getBaseDir('var') . DS . 'cache');

			// Clean the Magento cache			
			Mage::app()->getCacheInstance()->flush();
			
			return $this;
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e->getMessage());
			
			throw $e;
		}
	}
	
	/*
	 * Download the ZIP file
	 *
	 * @param string $url
	 * @return false|string
	 */
	protected function _downloadZipFile($url = self::MODULE_SOURCE)
	{
		$tempTarget = Mage::getBaseDir('var') . DS .'Fishpig_Wordpress_Integration.zip';
		
		if (is_file($tempTarget)) {
			unlink($tempTarget);
		}

		if (function_exists('curl_init')) {
			$ch = curl_init($url);
			
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			
			$rawData = curl_exec($ch);
			
			if(curl_errno($ch)){
				throw new Exception('cUrl(' . curl_errno($ch) . '): ' . curl_error($ch));
			}
			
			curl_close($ch);

			file_put_contents($tempTarget, $rawData);
		}
		else if ($rawData = file_get_contents($url)) {
			file_put_contents($tempTarget, $rawData);
		}
		
		if (!is_file($tempTarget) || filesize($tempTarget) === 0) {
			throw new Exception('Unable to download and/or save ZIP file.');
		}
		
		return $tempTarget;
	}
	
	/*
	 * Extract the ZIP file
	 *
	 * @param string $file
	 * @return
	 */
	protected function _extractZipFile($file)
	{
		$unzippedDir = dirname($file) . DS . 'magento1-wordpress-integration-master';
		
		if (is_dir($unzippedDir)) {
			$this->_removeDir($unzippedDir);
		}
		
		if (!is_file($file)) {
			throw new Exception('Zip file does not exist so cannot extract.');
		}
		
		if (@class_exists('ZipArchive')) {
			$zip = new ZipArchive($file);
			
			if ($zip->open($file) === true) {
				$zip->extractTo(dirname($unzippedDir));
				$zip->close();
			}
		}
		else {
			$originalDir = getcwd();
			
			shell_exec('cd ' . dirname($unzippedDir) . ';unzip ' . $file . ';cd ' . $originalDir);
		}

		if (!is_dir($unzippedDir)) {
			shell_exec(sprintf('cd %s && unzip %s', dirname($unzippedDir), $file));
		}
		
		if (!is_dir($unzippedDir)) {
			throw new Exception('Unable to extract Zip file.');
		}
				
		return $unzippedDir;
	}
	
	/**
	 * Delete a directory (that contains files)
	 *
	 * @param string $dir
	 * @return void
	 */
	protected function _removeDir($dir)
	{
		$files = array_reverse($this->_scanDir($dir));

		if (count($files) > 0) {
			foreach($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}	
				else if (is_dir($file)) {
					rmdir($file);
				}
			}
		}
		
		rmdir($dir);
	}
	
	/**
	 * Scan $dir and return all directories and files in an array
	 *
	 * @param string $dir
	 * @param bool $reverse = false
	 * @return array
	 */
	protected function _scanDir($dir)
	{
		$files = array();
		
		foreach(scandir($dir) as $file) {
			if (trim($file, '.') === '') {
				continue;
			}
			
			$tmp = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
			$files[] = $tmp;
	
			if (is_dir($tmp)) {
				$files = array_merge($files, $this->_scanDir($tmp));
			}
		}

		return $files;
	}
	
	/*
	 * Get the current version of the module
	 *
	 * @return false|string
	 */
	public function getCurrentVersion()
	{
		if ($etcDir = Mage::getModuleDir('etc', 'Fishpig_Wordpress')) {
			if ($data = file_get_contents($etcDir . DS . 'config.xml')) {
				if (preg_match('/<version>([^<]+)<\/version>/U', $data, $versionMatch)) {
					return $versionMatch[1];
				}
			}
		}

		return false;
	}
	
	/*
	 * Determine if the module is installed using git
	 *
	 * @return bool
	 */
	public function isGit()
	{
		return is_dir(dirname(dirname(dirname(dirname(dirname(dirname(realpath(Mage::getModuleDir('etc', 'Fishpig_Wordpress')))))))) . DS . '.git');
	}
}
