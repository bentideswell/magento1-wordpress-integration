<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_User extends Fishpig_Wordpress_Model_Abstract
{
	/**
	 * Entity meta infromation
	 *
	 * @var string
	 */
	protected $_metaTable = 'wordpress/user_meta';	
	protected $_metaTableObjectField = 'user_id';
	protected $_metaHasPrefix = true;

	/**
	 * Event information
	 *
	 * @var string
	*/
	protected $_eventPrefix = 'wordpress_user';
	protected $_eventObject = 'user';

	/**
	 * Retrieve the column name of the primary key fields
	 *
	 * @return string
	 */
	public function getMetaPrimaryKeyField()
	{
		return 'umeta_id';
	}
	
	public function _construct()
	{
		$this->_init('wordpress/user');
	}
	
	/**
	 * Load a user by an email address
	 *
	 * @param string $email
	 * @return $this
	 */
	public function loadByEmail($email)
	{
		return $this->load($email, 'user_email');
	}
	
	/**
	 * Get the URL for this user
	 *
	 * @return string
	 */
	public function getUrl()
	{
		if (!$this->hasUrl()) {
			$this->setUrl(Mage::helper('wordpress')->getUrl('author/' . urlencode($this->getUserNicename())) . '/');
		}
		
		return $this->_getData('url');
	}

	/**
	 * Load the WordPress user model associated with the current logged in customer
	 *
	 * @return Fishpig_Wordpress_Model_User
	 */
	public function loadCurrentLoggedInUser()
	{
		return $this->getResource()->loadCurrentLoggedInUser($this);
	}
	
	/**
	 * Retrieve the table prefix
	 * This is also used to prefix some fields (roles)
	 *
	 * @return string
	 */
	public function getTablePrefix()
	{
		return Mage::helper('wordpress/app')->getTablePrefix();
	}
	
	/**
	 * Retrieve the user's role
	 *
	 * @return false|string
	 */
	public function getRole()
	{
		if ($roles = $this->getMetaValue($this->getTablePrefix() . 'capabilities')) {
			foreach(unserialize($roles) as $role => $junk) {
				return $role;
			}
		}
		
		return false;
	}
	
	/**
	 * Set the user's role
	 *
	 * @param string $role
	 * @return $this
	 */
	public function setRole($role)
	{
		$this->setMetaValue($this->getTablePrefix() . 'capabilities', serialize(array($role => '1')));
		
		return $this;
	}
	
	/**
	 * Retrieve the user level
	 *
	 * @return int
	 */
	public function getUserLevel()
	{
		return $this->getMetaValue($this->getTablePrefix() . 'user_level');
	}
	
	/**
	 * Set the user level
	 *
	 * @param int $level
	 * @return $this
	 */
	public function setUserLevel($level)
	{
		$this->setMetaValue($this->getTablePrefix() . 'user_level', $level);
		return $this;
	}
	
	/**
	 * Retrieve the users first name
	 *
	 * @return string
	 */
	public function getFirstName()
	{
		return $this->getMetaValue('first_name');
	}
	
	/**
	 * Set the users first name
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setFirstName($name)
	{
		$this->setMetaValue('first_name', $name);
		return $this;
	}
	
	/**
	 * Retrieve the users last name
	 *
	 * @return string
	 */
	public function getLastName()
	{
		return $this->getMetaValue('last_name');
	}
	
	/**
	 * Set the users last name
	 *
	 * @param string $name
	 * @return $this
	 */
	public function setLastName($name)
	{
		$this->setMetaValue('last_name', $name);
		return $this;
	}
	
	/**
	 * Retrieve the user's nickname
	 *
	 * @return string
	 */
	public function getNickname()
	{
		return $this->getMetaValue('nickname');
	}
	
	/**
	 * Set the user's nickname
	 *
	 * @param string $nickname
	 * @return $this
	 */
	public function setNickname($nickname)
	{
		$this->setMetaValue('nickname', $nickname);
		return $this;
	}

	/**
	 * Retrieve the URL for Gravatar
	 *
	 * @return string
	 */
	public function getGravatarUrl($size = 50)
	{
		return "http://www.gravatar.com/avatar/" . md5(strtolower(trim($this->getUserEmail()))) . "?d=" . urlencode( $this->_getDefaultGravatarImage() ) . "&s=" . $size;
	}
	
	/**
	 * Retrieve the URL to the default gravatar image
	 *
	 * @return string
	 */
	protected function _getDefaultGravatarImage()
	{
		return '';
	}
	
	/**
	 * Retrieve the user's photo
	 * The UserPhoto plugin must be installed in WordPress
	 *
	 * @param bool $thumb
	 * @return null|string
	 */
	public function getUserPhoto($thumb = false)
	{
		$dataKey = $thumb ? 'userphoto_thumb_file' : 'userphoto_image_file';
		
		if (!$this->hasData($dataKey)) {
			if ($photo = $this->getCustomField($dataKey)) {
				$this->setData($dataKey, Mage::helper('wordpress')->getFileUploadUrl() . 'userphoto/' . $photo);
			}
			else if (Mage::helper('wordpress')->getWpOption('userphoto_use_avatar_fallback')) {
				if ($thumb) {
					$this->setData($dataKey, $this->getGravatarUrl(Mage::helper('wordpress')->getWpOption('userphoto_thumb_dimension')));
				}
				else {
					$this->setData($dataKey, $this->getGravatarUrl(Mage::helper('wordpress')->getWpOption('userphoto_maximum_dimension')));
				}
			}
		}
		
		return $this->_getData($dataKey);
	}
	
	/**
	 * Retrieve the default user role from the WordPress Database
	 *
	 * @return string
	 */
	public function getDefaultUserRole()
	{
		if (($role = trim(Mage::helper('wordpress')->getWpOption('default_role', 'subscriber'))) !== '') {
			return $role;
		}

		return 'subscriber';
	}
}
