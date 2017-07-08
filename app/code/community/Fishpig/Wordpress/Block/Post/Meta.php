<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Block_Post_Meta extends Fishpig_Wordpress_Block_Abstract
{
	/**
	 * Retrieve the category string (Posted in %s, %s and %s)
	 *
	 * @param Fishpig_Wordpress_Model_Post $post,
	 * @param array $params = array
	 * @return string
	 */
	public function getTermsAsHtml(Fishpig_Wordpress_Model_Post $post, $taxonomy)
	{
		$html = array();
		$taxonomy = Mage::helper('wordpress/app')->getTaxonomy($taxonomy);
		
		if ($taxonomy) {
			$terms = $taxonomy->getPostTermsCollection($post);
			
			if (count($terms) > 0) {
				foreach($terms as $term) {
					$html[] = $this->_generateAnchor($term->getUrl(), $term->getName());
				}
			}
		}
		
		return implode(', ', $html);
	}
	
	/**
	 * Retrieve the author string
	 *
	 * @param Fishpig_Wordpress_Model_Post $post,
	 * @param array $params = array
	 * @return string
	 */
	public function getAuthorString(Fishpig_Wordpress_Model_Post $post)
	{
		$author = $post->getAuthor();
		
		return $this->_generateAnchor($author->getUrl(), $author->getDisplayName());
	}
	
	/**
	 * Generate an anchor tag
	 *
	 * @param string $href
	 * @param string $anchor
	 * @param array $params = array
	 * @return string
	 */
	protected function _generateAnchor($href, $anchor)
	{
		return sprintf('<a href="%s">%s</a>', $href, $anchor);
	}
	
	/**
	 * Determine whether previous/next links are enabled in the config
	 *
	 * @return bool
	 */
	public function canDisplayPreviousNextLinks()
	{
		return $this->_getData('display_previous_next_links');
	}
	
	/**
	 * Retrieve the category string (Posted in %s, %s and %s)
	 *
	 * @param Fishpig_Wordpress_Model_Post $post,
	 * @return string
	 */
	public function getCategoryString(Fishpig_Wordpress_Model_Post $post, array $params = array())
	{
		return $this->getTermsAsHtml($post, 'post_category');
	}
	
	/**
	 * Retrieve the tag string (tagged with %s, %s and %s)
	 *
	 * @param Fishpig_Wordpress_Model_Post $post,
	 * @return string
	 */
	public function getTagString(Fishpig_Wordpress_Model_Post $post, array $params = array())
	{
		return $this->getTermsAsHtml($post, 'post_tag');
	}
	
	/**
	 * Determine whether a post has tags
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return bool
	 */
	public function hasTags(Fishpig_Wordpress_Model_Post $post)
	{
		return trim($this->getTermsAsHtml($post, 'post_tag')) !== '';
	}
}
