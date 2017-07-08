<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Sidebar_Widget_Instagram extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->__('Instagram');
	}
	
	/**
	  *
	 **/
	protected function _beforeToHtml()
	{
		if (!$this->getUsername()) {
			echo __LINE__;
			return false;
		}

		try {
			$this->_getImageData();
		}
		catch(Exception $e) {
			exit($e);
			Mage::helper('wordpress')->log($e->getMessage());
		}
	}
	
	/**
	  *
	 **/
	public function getPostUrl($image)
	{
		return 'https://www.instagram.com/p/' . $image->getCode() . '/?taken-by=' . urlencode($this->getInstagramUsername());
	}

	/**
	  *
	 **/
	protected function _getImageData()
	{
		$cacheFile = $this->_getCacheFile();
		
		$url = 'https://www.instagram.com/' . $this->getUsername() . '/';
		
		if (!is_file($cacheFile)) {
			if ($data = file_get_contents($url)) {
				// We have data via file_get_contents
			}
			else {
				throw new Exception('CURL not implemented yet in Instagram widget.');
			}
		
			if (strpos($data, 'window._sharedData') === false) {
				throw new Exception('Unable to parse response.');
			}

			$buffer = substr($data, 0, strpos($data, 'window._sharedData'));		
			$data = substr($data, strrpos($buffer, '<script'));
			$data = substr($data, 0, strpos($data, '</script>'));
			$data = preg_replace('/<script[^>]{1,}>/', '', $data);
			$data = rtrim(trim(str_replace('window._sharedData =', '', $data)), ';');

		
			$data = json_decode($data, true);

			if (!$data) {
				throw new Exception('Unable to parse JSON object.');
			}
		
			file_put_contents($cacheFile, json_encode($data));
		}
		else {
			$data = json_decode(file_get_contents($cacheFile), true);
		}
		
		$images = array_values($data['entry_data']['ProfilePage'][0]['user']['media']['nodes']);

		foreach($images as $key => $value) {
			$images[$key] = new Varien_Object($value);
		}
		

		if ((int)$this->getMaxImages() > 0 && (int)$this->getMaxImages() < count($images)) {
			$images = array_slice($images, 0, (int)$this->getMaxImages());
		}
		
		$this->setImages($images);
		
		return parent::_beforeToHtml();
	}
	
	/**
	  *
	 **/
	protected function _getCacheFile()
	{		
		return Mage::getBaseDir('var') . DS . 'cache' . DS .  'instagram-' . date('Ymd') . '-' . $this->_getCacheDateKey() . '-' . md5($this->getInstagramUsername()) . '.cache';
	}
	
	/**
	  *
	 **/
	protected function _getCacheDateKey()
	{
		$hour = date('G');

		return $hour < $this->getRefreshByHour() ? 1 : (int)($hour / $this->getRefreshByHour()) + 1;
	}
	
	/**
	  *
	 **/
	public function getRefreshByHour()
	{
		$hours = (int)$this->_getData('refresh_by_hour');
		
		return $hours > 0 ? $hours : 0;
	}
}
