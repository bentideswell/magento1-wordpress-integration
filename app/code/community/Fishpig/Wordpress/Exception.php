<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Exception extends Mage_Core_Exception
{
	/**
	 *  Status flags
	 *
	 * const int
	 */
	const STATUS_SUCCESS = 1;
	const STATUS_WARNING = 2;
	const STATUS_ERROR = 3;
	
	/**
	 * Retrieve the message
	 *
	 * @return string
	 */
	public function message()
	{
		return $this->message;
	}
	
	/**
	 * Create a new instance of this object
	 *
	 * @param string $title
	 * @param string $longMessage
	 * @param int $status = 3
	 * @return $this
	 */
	static public function factory($title, $longMessage, $status = 3)
	{
		$e = new Fishpig_Wordpress_Exception($title, $status);

		return $e->addMessage(Mage::getModel('core/message')->error($longMessage));
	}
	
	/**
	 * Create a new instance of this object (success)
	 *
	 * @param string $title
	 * @param string $longMessage
	 * @return $this
	 */
	static public function success($title, $longMessage = '')
	{
		return self::factory($title, $longMessage, self::STATUS_SUCCESS);
	}

	/**
	 * Create a new instance of this object (warning)
	 *
	 * @param string $title
	 * @param string $longMessage
	 * @return $this
	 */	
	static public function warning($title, $longMessage = '')
	{
		return self::factory($title, $longMessage, self::STATUS_WARNING);
	}
	
	/**
	 * Create a new instance of this object (error)
	 *
	 * @param string $title
	 * @param string $longMessage
	 * @return $this
	 */
	static public function error($title, $longMessage = '')
	{
		return self::factory($title, $longMessage, self::STATUS_ERROR);
	}
	
	/**
	 * Retrieve the long message
	 *
	 * @return string
	 */
	public function getLongMessage()
	{
		$longMessage = '';
		
		foreach($this->getMessages() as $message) {
			$longMessage .= $message->getCode();
		}
		
		return $longMessage;
	}
}