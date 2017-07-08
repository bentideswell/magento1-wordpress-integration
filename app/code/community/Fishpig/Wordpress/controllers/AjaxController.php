<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_AjaxController extends Mage_Core_Controller_Front_Action
{
	/**
	 * Allow retireval of layout handles via ajax
	 *
	 * @return void
	 */
	public function handleAction()
	{
		if (($handle = trim($this->getRequest()->getParam('handle'))) === '') {
			return $this->_forward('noRoute');
		}

		$this->loadLayout('wordpress_ajax_' . $handle);
		$this->renderLayout();
	}
	
	/**
	 * Allow retireval of layout blocks via ajax
	 *
	 * @return void
	 */
	public function blockAction()
	{
		if (($name = trim($this->getRequest()->getParam('block'))) === '') {
			return $this->_forward('noRoute');
		}
		
		$block = Mage::getSingleton('core/layout')->createBlock('wordpress/' . $name);
		
		if (!$block) {
			return $this->_forward('noRoute');
		}

		if ($params = $this->getRequest()->getParams()) {
			foreach($params as $key => $value) {
				if ($key === 'template') {
					$block->setTemplate($value);	
				}
				else {
					$block->setData($key, $value);
				}
			}
		}
		
		$this->getResponse()->setBody($block->toHtml());
	}
}
