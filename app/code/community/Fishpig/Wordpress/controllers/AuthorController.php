<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_AuthorController extends Fishpig_Wordpress_Controller_Abstract
{
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
	 * @return false|Fishpig_Wordpress_Model_Post_Category
	 */
	public function getEntityObject()
	{
		return $this->_initAuthor();
	}
	
	/**
	  * Display the author page and list posts
	  *
	  */
	public function viewAction()
	{
		$author = $this->_initAuthor();
		
		$this->_addCustomLayoutHandles(array(
			'wordpress_post_list',
			'wordpress_author_view', 
			'wordpress_author_view_' . $author->getId(),
		));
			
		$this->_initLayout();
	
		$this->_title($author->getDisplayName());

		$this->renderLayout();
	}

	/**
	 * Display the author bio
	 *
	 * @return void
	 */
	public function bioAction()
	{
		$author = $this->_initAuthor();
		
		$this->_addCustomLayoutHandles(array(
			'wordpress_author_bio', 
			'wordpress_author_bio_' . $author->getId(),
		));
			
		$this->_initLayout();
	
		$this->_title($author->getDisplayName());

		$this->renderLayout();
	}

	/**
	 * Load user based on URI
	 *
	 * @return false|Fishpig_Wordpress_Model_User
	 */
	protected function _initAuthor()
	{
		if (($author = Mage::registry('wordpress_author')) !== null) {
			return $author;
		}

		$author = Mage::getModel('wordpress/user')->load($this->getRequest()->getParam('author'), 'user_nicename');

		if ($author->getId()) {
			Mage::register('wordpress_author', $author);

			return $author;
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
		$author = $this->getEntityObject();
		
		$objects['author_nolink'] = array('label' => $this->__('Author'));
		$objects['author'] = array('link' => $author->getUrl(), 'label' => $author->getDisplayName());
	}
}
