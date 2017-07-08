<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Ajax extends Mage_Core_Block_Text
{
	/**
	 * Retrieve the ID of the block
	 *
	 * @return string
	 */
	public function getId()
	{
		if (!$this->hasId()) {
			$this->setId(
				'wp-ajax-' . substr(md5($this->getType() . '_' . $this->getHandle() . $this->getBlock() . rand(1111, 9999)), 0, 6) . rand(1111, 9999)
			);
		}
		
		return $this->_getData('id');
	}
	
	/**
	 * Retrieve the request type
	 * This can either be 'block' or 'handle'
	 *
	 * @return string
	 */	
	public function getType()
	{
		return $this->hasBlock() ? 'block' : 'handle';
	}
	
	/**
	  * Retrieve the URL of the blog we are requesting
	  *
	  * @return string
	  */
	public function getBlogUrl()
	{
		if ($blogUrl = $this->_getData('blog_url')) {
			if (strpos($blogUrl, 'http') === false) {
				$blogUrl = Mage::getUrl('', array('_direct' => $blogUrl));
			}
			
			return rtrim($blogUrl, '/') . '/';
		}
		
		return false;
	}
	
	/**
	 * Retrieve the URL used for the AJAX call
	 *
	 * @return string
	 */
	public function getAjaxUrl()
	{
		if ($this->hasAjaxUrl()) {
			return $this->_getData('ajax_url');
		}

		$url = $this->getBlogUrl() . 'ajax/' . $this->getType() . '/' 
			. ($this->getType() === 'block' ? $this->getBlock() : $this->getHandle());

		$params = array_diff_key($this->getData(), array(
			'type' => '', 
			'block' => '',
			'handle' => '',
			'blog_url' => '',
			'type' => '',
			'block_params' => '',
			'id' => '',
			'module_name' => '',
			'loader' => '',
		));
		
		$url .= '?' . http_build_query($params);
		
		$this->setAjaxUrl($url);
		
		return $url;
	}
	
	/**
	 * Retrieve the loader HTML
	 * Specify loader="1" (setLoader) to include AJAX loading image
	 *
	 * @return string
	 */
	protected function _getLoader()
	{
		if ($this->getLoader()) {
			return sprintf('<img src="%s" alt="%s" />', $this->getSkinUrl('wordpress/ajax-loader.gif'), $this->__('Loading'));
		}
		
		return '';
	}
	
	/**
	 * Set the HTML output
	 *
	 * @return $this
	 */
	protected function _beforeToHtml()
	{
		$html = '<div id="%s">%s</div><script type="text/javascript">new Ajax.Updater("%s", "%s", {});</script>';
		
		$this->setText(sprintf($html, $this->getId(), $this->_getLoader(), $this->getId(), $this->getAjaxUrl()));

		return parent::_beforeToHtml();
	}
}
