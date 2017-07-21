<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Image extends Fishpig_Wordpress_Model_Post_Attachment_Abstract
{
	public function _construct()
	{
		$this->_init('wordpress/image');
	}
	
	/**
	 * Retrieve the thumbnail image URL
	 *
	 * @return string
	 */
	public function getThumbnailImage()
	{
		return $this->_getImagePath('thumbnail');
	}

	/**
	 * Retrieve the medium image URL
	 *
	 * @return string
	 */	
	public function getMediumImage()
	{
		return $this->_getImagePath('medium');
	}

	/**
	 * Retrieve the large image URL
	 *
	 * @return string
	 */
	public function getLargeImage()
	{
		return $this->_getImagePath('large');
	}
	
	/**
	 * Retrieve the fullsize image URL
	 *
	 * @return string
	 */
	public function getFullSizeImage()
	{
		return $this->_getImagePath();
	}

	/**
	 * Retrieve the post thumbnail image URL
	 *
	 * @return string
	 */
	public function getPostThumbnailImage()
	{
		return $this->_getImagePath('post-thumbnail');
	}

	/**
	 * Retrieve any available image URL
	 *
	 * @return string
	 */
	public function getAvailableImage()
	{
		if ($sizes = $this->getSizes()) {
			foreach($sizes as $type => $data) {
				return $this->_getImagePath($type);
			}
		}

		return $this->_getImagePath();
	}
	
	/**
	 * Retrieve the an image URL by type
	 *
	 * @param string $type = 'thumbnail'
	 * @return string
	 */
	public function getImageByType($type = 'thumbnail')
	{
		return $this->_getImagePath($type);
	}
	
	/**
	 * Retrieve the an image URL by type
	 *
	 * @param string $type = 'thumbnail'
	 * @return string
	 */
	protected function _getImagePath($type = null)
	{
		if ($this->isSvg()) {
			return $this->getData('guid');
		}
		
		$filename = null;
		
		if ($type == null) {
			$filename = basename($this->getFile());
		}
		else {
			$sizes = $this->getSizes();

			if (isset($sizes[$type]['file'])) {
				$filename = $sizes[$type]['file'];
			}
		}
	
		if (!$filename) {
			return null;
		}
		
		return $this->_getThisImageUrl().$filename;
	}
	
	/*
	 * Determine if the current image is an SVG image
	 * These cannot be resized by WordPress so all requests for a different image
	 * should return the upload URL (this is stored as guid by WordPress)
	 *
	 * @return bool
	 */
	public function isSvg()
	{
		return $this->getPostMimeType() === 'image/svg+xml';
	}
	
	/**
	 * Retrieve the URL to the folder that the image is stored in
	 *
	 * @return string
	 */
	protected function _getThisImageUrl()
	{
		$url = $this->getFileUploadUrl() . dirname($this->getFile()) . '/';
		
		return Mage::app()->getStore()->isCurrentlySecure()
			? str_replace('http://', 'https://', $url)
			: $url;
	}
	
	/**
	 * Retrieve the upload URL
	 *
	 * @return string
	 */
	public function getFileUploadUrl()
	{
		return Mage::helper('wordpress')->getFileUploadUrl();
	}
	
	/**
	 * Retrieve the alt text for the image
	 *
	 * @return string
	 */
	public function getAltText()
	{
		return $this->getMetaValue('image_alt');
	}
	
	/**
	 * Retrieve the description for the image
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->_getData('post_content');
	}
	
	/**
	 * Retrieve the title for the image
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_getData('post_title');
	}
	
	/**
	 * Retrieve the caption for the image
	 *
	 * @return string
	 */
	public function getCaption()
	{
		return $this->_getData('post_excerpt');
	}
}
