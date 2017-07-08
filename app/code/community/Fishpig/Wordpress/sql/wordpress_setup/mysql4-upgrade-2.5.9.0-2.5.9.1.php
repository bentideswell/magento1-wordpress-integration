<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
	
	$this->startSetup();

	try {
		$table = $this->getTable('core/config_data');
		
		$updates = array(
			array(
				'cond' => array('path' => 'wordpress/database/is_different_db', 'value' => '1'),
				'data' => array('value' => '4'),
			),
			array(
				'cond' => array('path' => 'wordpress/database/is_different_db', 'value' => '0'),
				'data' => array('value' => '1'),
			),
			array(
				'cond' => array('path' => 'wordpress/database/is_different_db', 'value' => '4'),
				'data' => array('value' => '0'),
			),
			array(
				'cond' => array('path' => 'wordpress/database/is_different_db'),
				'data' => array('path' => 'wordpress/database/is_shared'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/template_default'),
				'data' => array('path' => 'wordpress/layout/default'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/template_default'),
				'data' => array('path' => 'wordpress/template/default'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/template_homepage'),
				'data' => array('path' => 'wordpress/template/homepage'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/template_post_list'),
				'data' => array('path' => 'wordpress/template/post_list'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/template_post_view'),
				'data' => array('path' => 'wordpress/template/post_view'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/template_page'),
				'data' => array('path' => 'wordpress/template/page'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/menu/enabled'),
				'data' => array('path' => 'wordpress/menu/enabled'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/menu/id'),
				'data' => array('path' => 'wordpress/menu/id'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/toplink_enabled'),
				'data' => array('path' => 'wordpress/toplink/enabled'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/toplink_label'),
				'data' => array('path' => 'wordpress/toplink/label'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/toplink_position'),
				'data' => array('path' => 'wordpress/toplink/position'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/associations/force_single_store'),
				'data' => array('path' => 'wordpress/integration/force_single_store'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/sidebar_left_empty'),
				'data' => array('path' => 'wordpress/misc/sidebar_left_empty'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/sidebar_right_empty'),
				'data' => array('path' => 'wordpress/misc/sidebar_right_empty'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/posts/autop'),
				'data' => array('path' => 'wordpress/misc/autop'),
			),
			array(
				'cond' => array('path' => 'wordpress_blog/layout/include_css'),
				'data' => array('path' => 'wordpress/misc/include_css'),
			),
		);

		foreach($updates as $update) {
			$cond = array();
			
			foreach($update['cond'] as $field => $value) {
				$cond[] = $this->getConnection()->quoteInto($field . '=?', $value);
			}

			try {
				$this->getConnection()->update($table, $update['data'], implode(' AND ', $cond));
			}
			catch (Exception $e) {
				Mage::helper('wordpress')->log($e);
			}
		}
	}
	catch (Exception $e) {
		Mage::helper('wordpress')->log($e);
		throw $e;
	}

	$this->endSetup();
