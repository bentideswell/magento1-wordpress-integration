<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Archive extends Varien_Object
{
	public function getName()
	{
		return Mage::helper('wordpress')->translateDate($this->_getData('name'));
	}
	
	/**
	 * Load an archive model by it's YYYY/MM
	 * EG: 2010/06
	 *
	 * @param string $value
	 */
	public function load($value)
	{
		$this->setId($value);
		
		if (strlen($value) == 7) {
			$this->setName(date('F Y', strtotime($value.'/01 01:01:01')));
			$this->setDateString(strtotime(str_replace('/', '-', $value) . ' 01:01:01'));
		}
		else {
			$this->setName(date('F j, Y', strtotime($value.' 01:01:01')));
			$this->setDateString(strtotime(str_replace('/', '-', $value) . '-01 01:01:01'));
			$this->setIsDaily(true);
		}
		
		return $this;
	}

	/**
	 * Get a date formatted string
	 *
	 * @param string $format
	 * @return string
	 */
	public function getDatePart($format)
	{
		return date($format, $this->getDateString());
	}

	/**
	 * Get the archive page URL
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return rtrim(Mage::helper('wordpress')->getUrl($this->getId()), '/') . '/';
	}
	
	/**
	 * Determine whether posts exist for this archive
	 *
	 * @return bool
	 */
	public function hasPosts()
	{
		if ($this->hasData('post_count')) {
			return $this->getPostCount() > 0;
		}

		return $this->getPostCollection()->count() > 0;
	}
	
	/**
	 * Retrieve a collection of blog posts
	 *
	 * @return Fishpig_Wordpress_Model_Mysql4_Post_Collection
	 */
	public function getPostCollection()
	{
		if (!$this->hasPostCollection()) {
			$collection = Mage::getResourceModel('wordpress/post_collection')
				->setFlag('source', $this)
				->addIsViewableFilter()
				->addArchiveDateFilter($this->getId(), $this->getIsDaily())
				->setOrderByPostDate();

			$this->setPostCollection($collection);
		}
		
		return $this->getData('post_collection');
	}
}
