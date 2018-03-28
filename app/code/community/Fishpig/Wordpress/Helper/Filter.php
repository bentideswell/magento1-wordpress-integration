<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Helper_Filter extends Fishpig_Wordpress_Helper_Abstract
{
	/**
	 * Process a string. Render shortcodes where possible
	 *
	 * @param string $string
	 * @return string
	 **/
	public function process($string)
	{
		return $this->applyFilters($string);
	}
	
	/**
	 * Applies a set of filters to the given string
	 *
	 * @param string $content
	 * @return string
	 */
	public function applyFilters($content)
	{
		if (Mage::getStoreConfigFlag('wordpress/misc/autop')) {
			$content = $this->addParagraphsToString($content);
		}

		$content = $this->doShortcode($content);
		
		if (strpos($content, '{{') !== false) {
			$content = Mage::helper('cms')->getBlockTemplateProcessor()->filter($content);
		}

		return $content;
	}

	/**
	 * Apply shortcodes to the content
	 *
	 * @param string &$content
	 * @param Fishpig_Wordpress_Model_Post $post
	 */
	public function doShortcode($content)
	{
		$transport = new Varien_Object(array('content' => trim(preg_replace('/(&nbsp;)$/', '', trim($content)))));

		Mage::dispatchEvent('wordpress_string_filter_before', array('content' => $transport));

		$content = $transport->getContent();
		
		Mage::helper('wordpress/shortcode_gist')->apply($content);
		Mage::helper('wordpress/shortcode_scribd')->apply($content);
		Mage::helper('wordpress/shortcode_dailymotion')->apply($content);
		Mage::helper('wordpress/shortcode_vimeo')->apply($content);
		Mage::helper('wordpress/shortcode_instagram')->apply($content);
		Mage::helper('wordpress/shortcode_youtube')->apply($content);
		Mage::helper('wordpress/shortcode_product')->apply($content);
		Mage::helper('wordpress/shortcode_caption')->apply($content);
		Mage::helper('wordpress/shortcode_gallery')->apply($content);
		Mage::helper('wordpress/shortcode_spotify')->apply($content);
		Mage::helper('wordpress/shortcode_code')->apply($content);
		Mage::helper('wordpress/shortcode_associatedProducts')->apply($content);
		
		$transport = new Varien_Object(array('content' => $content));
				
		Mage::dispatchEvent('wordpress_shortcode_apply', array('content' => $transport));
		
		$content = $transport->getContent();
		
		$transport = new Varien_Object(array('content' => $content));
				
		Mage::dispatchEvent('wordpress_string_filter_after', array('content' => $transport));

		return $transport->getContent();
	}

	/**
	 * Add paragraph tags to the content
	 *
	 * @param string $content
	 */
	public function addParagraphsToString($content)
	{
		$content = $this->_addParagraphsToString($content);
		
		$content = preg_replace('/<p>(\[|<div)/', '$1', $content);
		$content = preg_replace('/\]<\/p>/', ']', $content);
		$content = preg_replace('/(<\/div>)<\/p>/', '$1', $content);
		
		return $content;
	}

	/**
	 * Actually do the HTML conversion
	 *
	 * @param string $content
	 */
	protected function _addParagraphsToString($content)
	{
		if (function_exists('wpautop')) {
			return wpautop($content);
		}

		if ($this->_retrieveAutoP()) {
			return fp_wpautop($content);
		}

		$protectedTags = array(
			'script',
			'style',
			'pre',
			'textarea',
		);
		
		$safe = array();
		
		foreach($protectedTags as $tag) {
			if (strpos($content, '<' . $tag) !== false) {
				if (preg_match_all('/(<' . $tag . '.*<\/' . $tag . '>)/siU', $content, $matches)) {
					foreach($matches[1] as $match) {
						$safe[] = $match;
						$content = str_replace($match, '<!--KEY' . (count($safe)-1) . '-->', $content);
					}
				}
			}
		}

		$pee = str_replace("\n", ' ', $content) . "\n";
	
		$pee = preg_replace('|<br />\s*<br />|', "\n\n", $pee);
		// Space things out a little
		$allblocks = '(?:table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|option|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary)';
		$pee = preg_replace('!(<' . $allblocks . '[^>]*>)!', "\n$1", $pee);
		$pee = preg_replace('!(</' . $allblocks . '>)!', "$1\n\n", $pee);
		$pee = str_replace(array("\r\n", "\r"), "\n", $pee); // cross-platform newlines
		if ( strpos($pee, '<object') !== false ) {
			$pee = preg_replace('|\s*<param([^>]*)>\s*|', "<param$1>", $pee); // no pee inside object/embed
			$pee = preg_replace('|\s*</embed>\s*|', '</embed>', $pee);
		}
		$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates

		// make paragraphs, including one at the end
		$pees = preg_split('/\n\s*\n/', $pee, -1, PREG_SPLIT_NO_EMPTY);
		$pee = '';

		foreach ( $pees as $tinkle ) {
			if (trim(strip_tags(trim($tinkle))) !== '') {
				$pee .= '<p>' . trim($tinkle, "\n") . "</p>\n";
			}
			else {
				$pee .= $tinkle . "\n";
			}
		}

		$pee = preg_replace('|<p>\s*</p>|', '', $pee); // under certain strange conditions it could create a P of entirely whitespace
		$pee = preg_replace('!<p>([^<]+)</(div|address|form)>!', "<p>$1</p></$2>", $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee); // don't pee all over a tag
		$pee = preg_replace("|<p>(<li.+?)</p>|", "$1", $pee); // problem with nested lists
		$pee = preg_replace('|<p><blockquote([^>]*)>|i', "<blockquote$1><p>", $pee);
		$pee = str_replace('</blockquote></p>', '</p></blockquote>', $pee);
		$pee = preg_replace('!<p>\s*(</?' . $allblocks . '[^>]*>)!', "$1", $pee);
		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*</p>!', "$1", $pee);

		$pee = preg_replace('!(</?' . $allblocks . '[^>]*>)\s*<br />!', "$1", $pee);
		$pee = preg_replace('!<br />(\s*</?(?:p|li|div|dl|dd|dt|th|pre|td|ul|ol)[^>]*>)!', '$1', $pee);
		$pee = preg_replace( "|\n</p>$|", '</p>', $pee );
	
		foreach(array('script', 'style') as $tag) {
			$pee = str_replace(array('<p><' . $tag, '</' . $tag . '></p>'), array('<' . $tag, '</' . $tag . '>'), $pee);
		}
		
		$pee = str_replace(array('<p>[', ']</p>'), array('[', ']'), $pee);

		$content = $pee;

		foreach($safe as $key => $value) {
			$content = str_replace('<!--KEY' . $key . '-->', $value, $content);
		}

		return $content;
	}

	/**
	 * Retrieve the autop function and evaluate
	 *
	 * @return bool
	 **/
	protected function _retrieveAutoP()
	{
		if (function_exists('fp_wpautop')) {
			return true;
		}

		$formattingFile = Mage::getModuleDir('', 'Fishpig_Wordpress') . DS . 'lib' . DS . 'wp' . DS . 'formatting.php';
#		$formattingFile = Mage::helper('wordpress')->getWordPressPath() . 'wp-includes' . DS . 'formatting.php';
		
		if (!is_file($formattingFile)) {
			return false;
		}
		
		$code = preg_replace('/\/\*\*.*\*\//Us', '', file_get_contents($formattingFile));

		$functions = array(
			'wpautop' => '',
			'wp_replace_in_html_tags' => '',
			'_autop_newline_preservation_helper' => '',
			'wp_html_split' => '',
			'get_html_split_regex' => '',
		);
		
		foreach($functions as $function => $ignore) {
			if (preg_match('/(function ' . $function . '\(.*)function/sU', $code, $matches)) {
				$functions[$function] = $matches[1];
			}
			else {
				return false;
			}
		}
		
		$code = preg_replace('/(' . implode('|', array_keys($functions)) . ')/', 'fp_$1', implode("\n\n", $functions));
		
		@eval($code);

		return function_exists('fp_wpautop');
	}
	

	/**
	 * Preserve new lines
	 * Used as callback in _addParagraphsToString
	 *
	 * @param array $matches
	 * @return string
	 */
	public function _preserveNewLines($matches)
	{
		return str_replace("\n", "<WPPreserveNewline />", $matches[0]);
	}
}
