<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_View extends Fishpig_Wordpress_Block_Post_Abstract
{
	/**
	  * Returns the HTML for the comments block
	  *
	  * @return string
	  */
	public function getCommentsHtml()
	{
		return $this->getChildHtml('comments');
	}

	/**
	 * Setup the comments block
	 *
	 */
	protected function _beforeToHtml()
	{
		if ($this->getChild('comments')) {
			$this->getChild('comments')->setPost($this->getPost());
		}
		
		$this->_initPostViewTemplate();

		return parent::_beforeToHtml();
	}
	
	/**
	 * Get the post renderer template
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return string
	 */
	protected function _initPostViewTemplate()
	{
		if ($this->getTemplate()) {
			return $this;
		}

		if ($viewTemplate = $this->getPost()->getTypeInstance()->getViewTemplate()) {
			return $this->setTemplate($viewTemplate);
		}
		else if ($this->getPost()->getPostViewTemplate()) {
			return $this->setTemplate($this->getPost()->getPostViewTemplate());
		}
		else {
			$this->setTemplate('wordpress/post/view.phtml');
		}

		return $this;
	}
}
