<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Sidebar_Widget_Tagcloud extends Fishpig_Wordpress_Block_Sidebar_Widget_Abstract
{
	/**
	 * Retrieve a collection of tags
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Tag_Collection
	 */
	public function getTags()
	{
		if ($this->hasTags()) {
			return $this->_getData('tags');
		}
		
		$this->setTags(false);
		
		$tags = Mage::getResourceModel('wordpress/term_collection')
			->addCloudFilter($this->getTaxonomy())
			->setOrderByName()
			->load();

		if (count($tags) > 0) {
			$max = 0;
			$hasPosts = false;
			
			foreach($tags as $tag) {
				$max = $tag->getCount() > $max ? $tag->getCount() : $max;
				
				if ($tag->getCount() > 0) {
					$hasPosts = true;
				}
			}
			
			if ($hasPosts) {
				$this->setMaximumPopularity($max);
				$this->setTags($tags);
			}
		}

		return $this->getData('tags');
	}
	
	/**
	 * Retrieve a font size for a tag
	 *
	 * @param Varien_Object $tag
	 * @return int
	 */
	public function getFontSize(Varien_Object $tag)
	{
		if ($this->getMaximumPopularity() > 0) {
			$percentage = ($tag->getCount() * 100) / $this->getMaximumPopularity();
			
			foreach($this->getFontSizes() as $percentageLimit => $default) {
				if ($percentage <= $percentageLimit) {
					return $default;
				}
			}
		}
		
		return 150;
	}
	
	/**
	 * Retrieve the default title
	 *
	 * @return string
	 */
	public function getDefaultTitle()
	{
		return $this->__('Tag Cloud');
	}
	
	/**
	 * Retrieve an array of font sizes
	 *
	 * @return array
	 */
	public function getFontSizes()
	{
		if (!$this->hasFontSizes()) {
			return array(
				25 => 90,
				50 => 100,
				75 => 120,
				90 => 140,
				100 => 150
			);
		}
		
		return $this->_getData('font_sizes');
	}
}
