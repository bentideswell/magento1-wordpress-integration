<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Menu extends Mage_Page_Block_Html_Topmenu
{
	/**
	 * Get top menu html
	 *
	 * @param string $outermostClass
	 * @param string $childrenWrapClass
	 * @return string
	 */
	public function getHtml($outermostClass = '', $childrenWrapClass = '')
	{
		$this->_menu->setOutermostClass($outermostClass);
		$this->_menu->setChildrenWrapClass($childrenWrapClass);

		return trim($this->_getHtml($this->_menu, $childrenWrapClass));
	}

	/**
	 * Load and render the menu
	 *
	 * @return bool
	 */
	protected function _beforeToHtml()
	{
		if ($this->getMenuId()) {
			$menu = Mage::getModel('wordpress/menu')->load($this->getMenuId());

			if ($menu->getId()) {
				$this->setMenu($menu);
				
				if ($menu->applyToTreeNode($this->_menu)) {
					if (($html = trim($this->getHtml())) !== '') {
						if ($this->includeWrapper()) {
							$html = sprintf('<ul %s>%s</ul>', $this->_getListParams(), $html);
						}
					
						$this->setMenuHtml($this->_beforeRenderMenuHtml($html));
					}
					
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Determine whether to include the wrapping UL tag
	 *
	 * @param bool $val = null
	 * @return $this|bool
	 */
	public function includeWrapper($val = null)
	{
		if (!is_null($val)) {
			return $this->setIncludeWrapper($val);
		}
		
		return is_null($this->getIncludeWrapper())
			? false
			: $this->getIncludeWrapper();
	}

	/*
	 * Generate the list element parameters
	 *
	 * @return string
	 */
	protected function _getListParams()
	{
		$params = array();
		
		if ($this->getListClass()) {
			$params[] = sprintf('class="%s"', $this->getListClass());
		}
		
		if ($this->getListId()) {
			$params[] = sprintf('id="%s"', $this->getListId());
		}
		
		return implode(' ', $params);
	}
	
	/**
	 * Add the wrapper div if required
	 *
	 * @param string $html
	 * @return string
	 */
	protected function _beforeRenderMenuHtml($html)
	{
		if ($this->getIncludeWrapper() || $this->getWrapperId() || $this->getWrapperClass()) {
			$params = array();
			
			if ($this->getWrapperId()) {
				$params[] = sprintf('id="%s"', $this->getWrapperId());
			}
			
			if ($this->getWrapperClass()) {
				$params[] = sprintf('class="%s"', $this->getWrapperClass());
			}
			
			return sprintf('<div %s>%s</div>', implode(' ', $params), $html);
		}	
		
		return $html;
	}
	
	/**
	 * Return the menu HTML
	 *
	 * @return string
	 */
    protected function _toHtml()
    {
    	if (!$this->getTemplate()) {
	        if ($this->_beforeToHtml() === false) {
	            return '';
	        }
	
	        return $this->getMenuHtml();
	    }
	    
	    return parent::_toHtml();
    }
    
    /**
     * Retrieve the Menu title
     *
     * @return string
     */
    public function getTitle()
    {
    	if ($this->getMenu()) {
	    	return $this->getMenu()->getName();
    	}
    	
    	return '';
    }

    /**
     * Escape the HTML. Allows for legacy
     *
     * @param string $data
     * @param array $allowedTags = null
     * @return string
     */
	public function escapeHtml($data, $allowedTags = null)
	{
		return Mage::helper('wordpress')->escapeHtml($data, $allowedTags);
	}
	
	/**
	 * Retrieve cache key data
	 *
	 * @return array
	*/
	public function getCacheKeyInfo()
	{
		$cacheId = parent::getCacheKeyInfo();
	
		$cacheId['menu_id'] = $this->getMenuId();
	
		return $cacheId;
	}
}
