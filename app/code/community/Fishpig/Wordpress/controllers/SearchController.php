<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_SearchController extends Fishpig_Wordpress_Controller_Abstract
{
    /**
     * If this is set in $_GET
     * Redirect to SEO URL
     *
     * @const string
     */
    const BROKEN_URL_PARAM_NAME = 'redirect_broken_url';

    /**
     * If Integrated search is installed, redirect if enabled
     *
     * @return $this
     */
    public function preDispatch()
    {
        $request = $this->getRequest();

        if (Mage::helper('wordpress')->isAddonInstalled('IntegratedSearch') && Mage::getStoreConfigFlag('wordpress/integratedsearch/blog')) {
            $q = $request->getParam('s') ? $request->getParam('s') : $request->getParam('q');
            $integratedSearchUrl = Mage::getUrl('catalogsearch/result', array(
                'page' => max(1, (int)$request->getParam('page', 1)), 
                '_query' => array('q' => $q)
            ));
            
            header('Location: ' . $integratedSearchUrl);
            exit;

            $this->_forceForwardViaException('index', 'result', 'catalogsearch', array(
                'q' => $request->getParam('s'),
            ));
        }
        
        if ($request->getParam(self::BROKEN_URL_PARAM_NAME)) {
            if ($seoSearchUrl = $this->_getSeoSearchUrl()) {
                $this->getResponse()->setRedirect($seoSearchUrl)->sendResponse();
                $request->setDispatched( true );
            }
        }

        return parent::preDispatch();
    }

    /**
     * @return string
     */
    protected function _getSeoSearchUrl()
    {
        $params = $this->getRequest()->getParams();
        
        if (empty($params['s'])) {
            return false;
        }
        
        $searchTerm = urlencode(strtolower(trim($params['s'])));
        
        unset($params['s']);
        
        if (isset($params[self::BROKEN_URL_PARAM_NAME])) {
            unset($params[self::BROKEN_URL_PARAM_NAME]);
        }
        
        $searchUrlKey = 'search/' . $searchTerm . '/' . ($params ? '?' . urldecode(http_build_query($params)) : '');

        return Mage::helper('wordpress')->getUrl($searchUrlKey);
    }
    
    /**
     *
     *
     */
    public function indexAction()
    {
        $this->_addCustomLayoutHandles(array(
            'wordpress_post_list',
            'wordpress_search_index',
        ));
        
        $this->_initLayout();

        $helper     = $this->getRouterHelper();
        $searchTerm = Mage::helper('wordpress')->escapeHtml($helper->getSearchTerm());
        
        $this->_title($this->__("Search results for: '%s'", $searchTerm));
        
        $this->renderLayout();
    }
    
    /**
     * Get the breadcrumbs for the entity
     *
     * @param  array $objects
     * @return array
     */
    protected function _getEntityCrumbs(array &$objects)
    {
        $objects['search_label'] = array('link' => '', 'label' => $this->__('Search'));
        $objects['search_value'] = array('link' => '', 'label' => Mage::helper('wordpress')->escapeHtml($this->getRouterHelper()->getSearchTerm()));
        
        return $objects;
    }        
}
