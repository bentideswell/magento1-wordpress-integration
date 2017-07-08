<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_System_Config_Backend_Encrypted extends Mage_Adminhtml_Model_System_Config_Backend_Encrypted
{
    /**
     * Decrypt value after loading
     * If no value present, do not  decrypt!
     */
    protected function _afterLoad()
    {
        $value = (string)$this->getValue();
        
        if ($value) {
        	parent::_afterLoad();
        }
    }
}
