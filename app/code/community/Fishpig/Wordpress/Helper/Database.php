<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Database extends Fishpig_Wordpress_Helper_Abstract
{
	/**
	 * Retrieve the read adapter
	 *
	 * @return Fishpig_Wordpress_Helper_App
	 */	
	public function connect()
	{
		return Mage::helper('wordpress/app')->init();
	}

	/**
	 * Retrieve the read adapter
	 *
	 * @return false|Varien_Db_Adapter_Pdo_Mysql
	 */	
	public function getReadAdapter()
	{
		return Mage::helper('wordpress/app')->getDbConnection();
	}
	
	/**
	 * Retrieve the write adapter
	 *
	 * @return false|Varien_Db_Adapter_Pdo_Mysql
	 */
	public function getWriteAdapter()
	{
		return Mage::helper('wordpress/app')->getDbConnection();
	}
}
