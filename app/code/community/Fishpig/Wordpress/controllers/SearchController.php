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
	 * If Integrated search is installed, redirect if enabled
	 *
	 * @return $this
	 */
	public function preDispatch()
	{
		if ($this->getRequest()->getParam('redirect_broken_url')) {
			$this->getResponse()
				->setRedirect(Mage::helper('wordpress')->getUrl('search/' . $this->getRequest()->getParam('s') . '/'))
				->sendResponse();

            $this->getRequest()->setDispatched( true );
		}
		else if (Mage::helper('wordpress')->isAddonInstalled('IntegratedSearch') && Mage::getStoreConfigFlag('wordpress/integratedsearch/blog')) {
			$this->_forceForwardViaException('index', 'result', 'catalogsearch', array(
				'q' => $this->getRequest()->getParam('s'),
			));
		}

		return parent::preDispatch();
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

		$helper = $this->getRouterHelper();

		$searchTerm = Mage::helper('wordpress')->escapeHtml($helper->getSearchTerm());
		
		$this->_title($this->__("Search results for: '%s'", $searchTerm));
		
		$this->addCrumb('search_label', array('link' => '', 'label' => $this->__('Search')));
		$this->addCrumb('search_value', array('link' => '', 'label' => $searchTerm));
		
		$this->renderLayout();
	}
}
