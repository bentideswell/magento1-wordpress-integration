<?php
/**
 * @category		Fishpig
 * @package		Fishpig_Wordpress
 * @license		http://fishpig.co.uk/license.txt
 * @author		Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_TermController extends Fishpig_Wordpress_Controller_Abstract
{
	/**
	 * Blocks used to generate RSS feed items
	 *
	 * @var string
	 */
	 protected $_feedBlock = 'term_view';
	 
	/**
	 * Used to do things en-masse
	 * eg. include canonical URL
	 *
	 * @return false|Fishpig_Wordpress_Model_Term
	 */
	public function getEntityObject()
	{
		return $this->_initTerm();
	}
	
	/**
	 * Ensure that the term loaded isn't a default term
	 * Default terms (post_category, tag etc) have their own controller
	 *
	 * @return $this|false
	 */
	public function preDispatch()
	{
		parent::preDispatch();
		
		if (!($term = $this->_initTerm())) {
			return false;
		}
		
		$taxonomy = $term->getTaxonomyInstance();
		
		if ($taxonomy->getCustomRoute()) {
			list($module, $controller, $action) = explode('/', $taxonomy->getCustomRoute());
			
			return $this->_forward($action, $controller, $module);
		}
	
		return $this;	
	}
	
	/**
	  * Display the term page and list associated posts
	  *
	  */
	public function viewAction()
	{
		$term = Mage::registry('wordpress_term');
		
		$this->_addCustomLayoutHandles(array(
			'wordpress_post_list',
			'wordpress_term_view',
			'wordpress_post_' . $term->getTaxonomyType() . '_view',  // Legacy
			'wordpress_' . $term->getTaxonomyType() . '_view',
			'wordpress_' . $term->getTaxonomyType() . '_' . $term->getId(), // Legacy
			'wordpress_' . $term->getTaxonomyType() . '_view_' . $term->getId(),
		));

		$this->_initLayout();
		
		$tree = array($term);
		$buffer = $term;
		
		while($buffer = $buffer->getParentTerm()) {
			array_unshift($tree, $buffer);
		}
		
		while($branch = array_shift($tree)) {
			$this->addCrumb('term_' . $branch->getId(), array(
				'link' => ($tree ? $branch->getUrl() : null), 
				'label' => $branch->getName())
			);

			$this->_title($branch->getName());
		}
		
		$this->renderLayout();
	}


	/**
	 * Initialise the term model
	 *
	 * @return false|Fishpig_Wordpress_Model_Term
	 */
	protected function _initTerm()
	{
		if (($term = Mage::registry('wordpress_term')) !== null) {
			return $term;
		}

		$term = Mage::getModel('wordpress/term')
			->setTaxonomy($this->getRequest()->getParam('taxonomy'))
			->load($this->getRequest()->getParam('id'));

		if (!$term->getId()) {
			return false;
		}
		
		Mage::register('wordpress_term', $term);

		return $term;
	}
}
