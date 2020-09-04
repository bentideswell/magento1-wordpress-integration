<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_List_Pager extends Mage_Page_Block_Html_Pager 
{
    /**
     * @var string
     */
    protected $baseUrl;
    
	/**
	 * Construct the pager and set the limits
	 *
	 */
	protected function _construct()
	{
		parent::_construct();	

		$this->setPageVarName('page');

		$this->updatePageSize($this->getPostsPerPage());
		
		$this->setFrameLength(5);
	}
	
	/*
	 *
	 * @return int
	 */
	public function getPostsPerPage()
	{
		return (int)$this->helper('wordpress')->getWpOption('posts_per_page', 10);
	}
	
	/**
	 * Update the number of items to display per page
	 *
	 * @param int $size
	 * @return $this
	**/
	public function updatePageSize($size)
	{
		$this->setDefaultLimit($size);
		$this->setLimit($size);
		
		$this->setAvailableLimit(array(
			$size => $size
		));
		
		return $this;
	}

    /**
     * @return string
     */
    protected function _getBaseUrl()
    {
        if ($this->baseUrl === null) {
    		$baseUrl = $this->getUrl('*/*/*', array(
    			'_current' => true,
    			'_escape' => true,
    			'_use_rewrite' => true,
    			'_nosid' => true,
    			'_query' => array('___refresh' => null),
    		));

            if (strpos($baseUrl, '/search') !== false) {
                $oBaseUrl = Mage::helper('core/url')->getCurrentUrl();

                if (strpos($oBaseUrl, 'catalogsearch') !== false) {
                    $baseUrl = $this->getUrl('*/*/*', array(
                        '_current' => true,
            			'_escape' => true,
                        '_nosid' => true,
                        'page' => null,
                        '_query' => array('___refresh' => null),
                    ));
                }
            }

            $this->baseUrl = $baseUrl;
		}
		
		return $this->baseUrl;
    }

	/**
	 * Return the URL for a certain page of the collection
	 *
	 * @return string
	 */
	public function getPagerUrl($params=array())
	{
		$pageVarName = $this->getPageVarName();

		$slug = isset($params[$pageVarName]) 
			? $pageVarName . '/' . $params[$pageVarName] . '/'
			: '';
		
		$slug = ltrim($slug, '/');
        $baseUrl = $this->_getBaseUrl();
		$queryString = '';
		
		if (strpos($baseUrl, '?') !== false) {
			$queryString = substr($baseUrl, strpos($baseUrl, '?'));
			$baseUrl = substr($baseUrl, 0, strpos($baseUrl, '?'));
		}
		
		return rtrim($baseUrl, '/') . '/' . $slug . $queryString;
	}
}
