<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_ArchiveController extends Fishpig_Wordpress_Controller_Abstract
{
	/**
	 * Set the feed blocks
	 *
	 * @var string
	 */
	protected $_feedBlock = 'archive_view';
	
	/*
	 * Enable pagination validation
	 *
	 * @var bool
	 */ 
	protected $_canValidatePagination = true;
	
	/**
	 * Used to do things en-masse
	 * eg. include canonical URL
	 *
	 * @return false|Fishpig_Wordpress_Model_Archive
	 */
	public function getEntityObject()
	{
		return $this->_initArchive();
	}
	
	/**
	  * Display the archive and list the posts
	  *
	  */
	public function viewAction()
	{
		$archive = Mage::registry('wordpress_archive');
		
		$this->_addCustomLayoutHandles(array(
			'wordpress_post_list',
			'wordpress_archive_view',
		));
			
		$this->_initLayout();

		$this->_title($archive->getName());

		$this->renderLayout();
	}

	/**
	 * Loads an archive model based on the URI
	 *
	 * @return false|Fishpig_Wordpress_Model_Archive
	 */
	protected function _initArchive()
	{
		if (($archive = Mage::registry('wordpress_archive')) !== null) {
			return $archive;
		}

		$date = trim(implode('/', array(
			$this->getRequest()->getParam('year'),
			$this->getRequest()->getParam('month'),
			$this->getRequest()->getParam('day'),
		)), '/');

		
		if ($archive = Mage::getModel('wordpress/archive')->load($date)) {
			if ($archive->hasPosts()) {
				Mage::register('wordpress_archive', $archive);

				return $archive;
			}
		}

		return false;
	}
	
	/*
	 * Get the breadcrumbs for the entity
	 *
	 * @param  array $objects
	 * @return void
	 */
	protected function _getEntityCrumbs(array &$objects)
	{
		$objects['archive_label'] = array('label' => Mage::helper('wordpress')->__('Archives'));
		$objects['archive'] = array('label' => $this->getEntityObject()->getName());
	}
}
