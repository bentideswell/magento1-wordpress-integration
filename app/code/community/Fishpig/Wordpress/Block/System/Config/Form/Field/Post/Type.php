<?php
/**
 * @category  Fishpig
 * @package  Fishpig_CrossLink
 * @license    http://fishpig.co.uk/license.txt
 * @author    Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_System_Config_Form_Field_Post_Type extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
	/**
	 * Prepare to render
	*/
	protected function _prepareToRender()
	{
		$this->addColumn('custom_type', array(
			'label' => $this->__('Custom Post Type'),
		));
	
		$this->_addAfter = false;
		$this->_addButtonLabel = $this->__('Add New Custom Post Type');
	}
}
