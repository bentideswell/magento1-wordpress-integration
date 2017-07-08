<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

	$this->startSetup();

	try {	
		// Delete old autologin table
		$this->getConnection()->query("DROP TABLE IF EXISTS {$this->getTable('wordpress_autologin')}");
		
		// Delete old CPT table
		$this->getConnection()->query("DROP TABLE IF EXISTS {$this->getTable('wordpress_addon_cpt_type')}");
		
		// Check for association tables
		Mage::helper('wordpress/associations')->checkForTables();
		
		// Clean duplicate users (old CS error)
		Mage::getResourceModel('wordpress/user')->cleanDuplicates();
	}
	catch (Exception $e) {
		Mage::helper('wordpress')->log($e);
	}

	$this->endSetup();
