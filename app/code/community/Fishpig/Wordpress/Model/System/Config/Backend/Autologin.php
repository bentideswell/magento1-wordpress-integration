<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_System_Config_Backend_Autologin extends Mage_Adminhtml_Model_System_Config_Backend_Encrypted
{
	/**
	 * Set the config value
	 * $value will usually be empty so reload config value with user ID
	 *
	 * @param string $value
	 * @return $this
	 */
	public function setValue($value)
	{
		if (!$value) {
			$value = Mage::getStoreConfig($this->_updatePath()->getPath());
		}

		 return parent::setValue($value);
	}
	
	/**
	 * Before saving the config data, update path
	 * Also decide whether to encrypt value
	 *
	 * @return $this
	 */
	protected function _beforeSave()
	{
		if ((string)$this->getValue()) {	
			$this->_updatePath();

			return parent::_beforeSave();
		}
		
		return $this;
	}
	
	/**
	 * After loading data, decide whether to decrypt
	 * Should not decrypt empty string
	 *
	 * @return $this
	 */
    protected function _afterLoad()
    {
        if ((string)$this->getValue()) {
        	$originalValue = $this->getValue();
        	
        	parent::_afterLoad();
        	
        	if ($this->_isValidString($originalValue) && !$this->_isValidString($this->getValue())) {
	        	$this->setValue($originalValue);
        	}
        }
        
        return $this;
    }
    
    /**
     * Modified to return $this
     *
     * @return $this
     */
    public function afterLoad()
    {
	    parent::afterLoad();
	    
	    return $this;
    }

    /**
     * Update the internal path data value
     * This should include _{{user_id}} on the end
     *
     * @return $this
     */
	protected function _updatePath()
	{
		if (!preg_match('/_[0-9]+$/', $this->getPath())) {
			$this->setPath(
				$this->getPath() . '_' . Mage::getSingleton('admin/session')->getUser()->getId()
			);
		}

		return $this;
	}
	
	/**
	 * Retrieve the username for the current Admin user
	 *
	 * @return string
	 */
	public function getUsername()
	{
		return $this->setPath('wordpress/autologin/username')
			->setValue('')
			->afterLoad()
			->getValue();
	}
	
	/**
	 * Retrieve the password for the current Admin user
	 *
	 * @return string
	 */
	public function getPassword()
	{
		return $this->setPath('wordpress/autologin/password')
			->setValue('')
			->afterLoad()
			->getValue();
	}
	
	/**
	 * Check whether $s is a valid string
	 * Some Magento instances don't encrypt the string so when we decrypt
	 * it breaks the string
	 *
	 * @param string $s
	 * @return bool
	 */
	protected function _isValidString($s)
	{
		return (bool)preg_match('//u', $s);
	}
}
