<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Feed_Post_Comment extends Fishpig_Wordpress_Block_Feed_Abstract
{
	/**
	 * Generate the entries and add them to the RSS feed
	 *
	 * @param Zend_Feed_Writer_Feed $feed
	 * @return $this
	 */
	protected function _addEntriesToFeed($feed)
	{
		$comments = Mage::getResourceModel('wordpress/post_comment_collection')
			->addCommentApprovedFilter()
			->addOrderByDate('desc');
		
		$this->_prepareItemCollection($comments);
		
		foreach($comments as $comment) {
			$entry = $feed->createEntry();

			if ($this->getSource()) {
				$entry->setTitle(
					Mage::helper('wordpress')->__('By: %s', $comment->getCommentAuthor())
				);
			}
			else {
				$entry->setTitle(
					Mage::helper('wordpress')->__('Comment on %s by %s', $comment->getPost()->getPostTitle(), $comment->getCommentAuthor())
				);
			}

			if (strpos($comment->getUrl(), 'http') !== false) {
				$entry->setLink($comment->getUrl());
			}

			if ($comment->getCommentAuthorEmail() && $comment->getCommentAuthor()) {
				$entry->addAuthor(array(
					'name' => $comment->getCommentAuthor(),
					'email' => $comment->getCommentAuthorEmail(),
				));
			}

			$entry->setDescription($comment->getCommentContent());
			$entry->setDateModified(strtotime($comment->getData('comment_date_gmt')));
			
			$feed->addEntry($entry);
		}

		return $this;
	}
	
	/**
	 * Apply the source filter if available
	 *
	 * @param $collection
	 * @return $this
	 */
	protected function _prepareItemCollection($collection)
	{
		if ($this->getSource()) {
			$collection->addPostIdFilter($this->getSource()->getId());
		}
		
		return parent::_prepareItemCollection($collection);
	}

	/**
	 * Retrieve the feed title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if ($this->getSource()) {
			return sprintf('Comments on: %s', $this->getSource()->getPostTitle());
		}
		else {
			return sprintf('Comments for %s', parent::getTitle());
		}
	}
}
