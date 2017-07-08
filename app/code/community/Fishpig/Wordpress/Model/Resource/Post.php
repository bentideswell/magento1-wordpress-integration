<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_Wordpress_Model_Resource_Post extends Fishpig_Wordpress_Model_Resource_Abstract
{
	/**
	 * Set the table and primary key
	 *
	 * @return void
	 */
	public function _construct()
	{
		$this->_init('wordpress/post', 'ID');
	}

	/**
	 * Custom load SQL
	 *
	 * @param string $field - field to match $value to
	 * @param string|int $value - $value to load record based on
	 * @param Mage_Core_Model_Abstract $object - object we're trying to load to
	 */
	protected function _getLoadSelect($field, $value, $object)
	{
		$select = $this->_getReadAdapter()->select()
			->from(array('e' => $this->getMainTable()))
			->where("e.{$field}=?", $value)
			->limit(1);

		$postType = $object->getPostType();

		if (!in_array($postType, array('*', ''))) {
			$select->where('e.post_type ' . (is_array($postType) ? 'IN' : '=') . ' (?)', $postType);
		}

		$select->columns(array('permalink' => $this->getPermalinkSqlColumn()));

		return $select;
	}
	
	public function completePostSlug($slug, $postId, $postType)
	{
		if (!preg_match_all('/(\%[a-z0-9_-]{1,}\%)/U', $slug, $matches)) {
			return $slug;
		}

		$matchedTokens = $matches[0];

		foreach($matchedTokens as $mtoken) {
			if ($mtoken === '%postnames%') {
				$slug = str_replace($mtoken, $postType->getHierarchicalPostName($postId), $slug);
			}
			else if ($taxonomy = Mage::helper('wordpress/app')->getTaxonomy(trim($mtoken, '%'))) {
				$termData = $this->getParentTermsByPostId(array($postId), $taxonomy->getTaxonomyType(), false);

				foreach($termData as $key => $term) {
					if ((int)$term['object_id'] === (int)$postId) {
						$slug = str_replace($mtoken, $taxonomy->getUriById($term['term_id'], false), $slug);
						
						break;
					}
				}
			}
		}

		return urldecode($slug);
	}
	
	/**
	 * Prepare a collection/array of posts
	 *
	 * @param mixed $posts
	 * @return $this
	 */
	public function preparePosts($posts)
	{
		foreach($posts as $post) {
			$post->setData(
				'permalink',
				$this->completePostSlug($post->getData('permalink'), $post->getId(), $post->getTypeInstance())
			);
		}
		
		return $this;
	}
	
	/**
	 * Get the category IDs that are related to the postIds
	 *
	 * @param array $postIds
	 * @param bool $getAllIds = true
	 * @return array|false
	 */
	public function getParentTermsByPostId($postId, $taxonomy = 'category')
	{
		$select = $this->_getReadAdapter()->select()
			->distinct()
			->from(array('_relationship' => $this->getTable('wordpress/term_relationship')), 'object_id')
			->where('object_id = (?)', $postId)
			->order('_term.term_id ASC');
			
		$select->join(
			array('_taxonomy' => $this->getTable('wordpress/term_taxonomy')),
			$this->_getReadAdapter()->quoteInto("_taxonomy.term_taxonomy_id = _relationship.term_taxonomy_id AND _taxonomy.taxonomy= ?", $taxonomy),
			'*'
		);
		
		$select->join(
			array('_term' => $this->getTable('wordpress/term')), 
			"`_term`.`term_id` = `_taxonomy`.`term_id`", 
			'name'
		);
		
		if (Mage::helper('wordpress')->isAddonInstalled('WordPressSEO')) {
			Mage::helper('wp_addon_yoastseo')->addPrimaryCategoryToSelect($select, $postId);
		}

		$select->reset('columns')
			->columns(array(
				$taxonomy . '_id' => '_term.term_id', 
				'term_id' => '_term.term_id',
				'object_id'
			))->limit(1);

		return $this->_getReadAdapter()->fetchAll($select);
				
		$wrapper = $this->_getReadAdapter()
			->select()
				->from(array('squery' => new Zend_Db_Expr('(' . (string)$select . ')')))
				->group('squery.object_id')
				->reset('columns')
				->columns(array(
					'object_id',
					'category_ids' => new Zend_Db_Expr("GROUP_CONCAT(`squery`.`term_id` ORDER BY `squery`.`name` ASC)"
				)));

		return $this->_getReadAdapter()->fetchAll($wrapper);
	}
		
	/**
	 * Get the permalink SQL as a SQL string
	 *
	 * @return string
	 */
	public function getPermalinkSqlColumn()
	{	
		if (!($postTypes = Mage::helper('wordpress/app')->getPostTypes())) {
			return false;
		}

		$sqlColumns = array();
		$fields = $this->getPermalinkSqlFields();

		foreach($postTypes as $postType) {	
			$tokens = $postType->getExplodedPermalinkStructure();				
			$sqlFields = array();

			foreach($tokens as $token) {
				if (substr($token, 0, 1) === '%' && isset($fields[trim($token, '%')])) {
					$sqlFields[] = $fields[trim($token, '%')];
				}
				else {
					$sqlFields[] = "'" . $token . "'";
				}
			}	
	
			if (count($sqlFields) > 0) {
				$sqlColumns[$postType->getPostType()] = ' WHEN `post_type` = \'' . $postType->getPostType() . '\' THEN (CONCAT(' . implode(', ', $sqlFields) . '))';
			}
		}

		return count($sqlColumns) > 0 
			? new Zend_Db_Expr(sprintf("CASE %s END", implode("", $sqlColumns)))
			: false;
	}
	
	/**
	 * Get permalinks by the URI
	 * Given a $uri, this will retrieve all permalinks that *could* match
	 *
	 * @param string $uri = ''
	 * @param array $postTypes = null
	 * @return false|array
	 */
	public function getPermalinksByUri($uri = '')
	{
		$originalUri = $uri;
		$permalinks = array();	

		if ($postTypes = Mage::helper('wordpress/app')->getPostTypes()) {
			$fields = $this->getPermalinkSqlFields();

			foreach($postTypes as $postType) {	
				if (false && $postType->isHierarchical()) {
					$tokens = $postType->getExplodedPermalinkStructure();
					
					if (count($tokens) === 2 && $tokens[0] === '%postname%' && strpos($tokens[1], '%') === false) {
						$hierarchicalRoutes = $postType->getHierarchicalPostNames();
	
						if ($hierarchicalRoutes) {
							foreach($hierarchicalRoutes as $routeId => $route) {
								if ($route === $originalUri) {
									$permalinks += array($routeId => $route);
								}
							}
						}

						continue;
					}
				}
				
				if (!($tokens = $postType->getExplodedPermalinkStructure())) {
					continue;
				}

				$uri = $originalUri;

				if ($postType->permalinkHasTrainingSlash()) {
					$uri = rtrim($uri, '/') . '/';
				}

				$filters = array();
				$lastToken = $tokens[count($tokens)-1];

				# Allow for trailing static strings (eg. .html)
				if (substr($lastToken, 0, 1) !== '%') {
					if (substr($uri, -strlen($lastToken)) !== $lastToken) {
						continue;
					}
					
					$uri = substr($uri, 0, -strlen($lastToken));
					
					array_pop($tokens);
				}

				try {
					for($i = 0; $i <= 1; $i++) {
						if ($i === 1) {
							$uri = implode('/', array_reverse(explode('/', $uri)));
							$tokens = array_reverse($tokens);
						}

						foreach($tokens as $key => $token) {
							if (substr($token, 0, 1) === '%') {
								if (!isset($fields[trim($token, '%')])) {
									if ($taxonomy = Mage::helper('wordpress/app')->getTaxonomy(trim($token, '%'))) {
										$endsWithPostname = isset($tokens[$key+1]) && $tokens[$key+1] === '/' 
											&& isset($tokens[$key+2]) && $tokens[$key+2] === '%postname%' 
											&& !isset($tokens[$key+3]);
										
										
										if ($endsWithPostname) {
											$uri = rtrim(substr($uri, strrpos(rtrim($uri, '/'), '/')), '/');
											continue;
										}
									}

									break;
								}
								
								if (isset($tokens[$key+1]) && substr($tokens[$key+1], 0, 1) !== '%') {
									$filters[trim($token, '%')] = substr($uri, 0, strpos($uri, $tokens[$key+1]));
									$uri = substr($uri, strpos($uri, $tokens[$key+1]));
								}
								else if (!isset($tokens[$key+1])) {
									$filters[trim($token, '%')] = $uri;
									$uri = '';
								}
								else {
									throw new Exception('Ignore me #1');
								}
							}
							else if (substr($uri, 0, strlen($token)) === $token) {
								$uri = substr($uri, strlen($token));
							}
							else {
								throw new Exception('Ignore me #2');
							}
							
							unset($tokens[$key]);
						}
					}
					
					if (isset($filters['post_id']) && isset($filters['postname'])) {
						if ((int)$filters['postname'] > 0 && (int)$filters['post_id'] === 0) {
							$temp = $filters['post_id'];
							$filters['post_id'] = $filters['postname'];
							$filters['postname'] = $temp;
						}
					}

					if ($buffer = $this->getPermalinks($filters, $postType)) {
						foreach($buffer as $routeId => $route) {
							if (rtrim($route, '/') === $originalUri) {
								$permalinks[$routeId] = $route;
								throw new Exception('Break');
							}
						}
						
#						$permalinks += $buffer;
					}
				}
				catch (Exception $e) {
					if ($e->getMessage() === 'Break') {
						break;
					}
					
					// Exception thrown to escape nested loops
				}
			}
		}

		return count($permalinks) > 0 ? $permalinks : false;
	}
	
	/**
	 * Get an array of post ID's and permalinks
	 * $filters is applied but if empty, all permalinks are returned
	 *
	 * @param array $filters = array()
	 * @return array|false
	 */
	public function getPermalinks(array $filters = array(), $postType)
	{
		$tokens = $postType->getExplodedPermalinkStructure();
		$fields = $this->getPermalinkSqlFields();
		
		$select = $this->_getReadAdapter()
			->select()
			->from(array('main_table' => $this->getMainTable()), array('id' => 'ID', 'permalink' => $this->getPermalinkSqlColumn()))
			->where('post_type = ?', $postType->getPostType())
			->where('post_status IN (?)', array('publish', 'protected', 'private'));

		foreach($filters as $field => $value) {
			if (isset($fields[$field])) {
				$select->where($fields[$field] . '=?', urlencode($value));
			}
		}

		if ($routes = $this->_getReadAdapter()->fetchPairs($select)) {
			foreach($routes as $id => $permalink) {
				$routes[$id] = urldecode($this->completePostSlug($permalink, $id, $postType));
			}

			return $routes;
		}
		
		return false;
	}

	/**
	 * Get the SQL data for the permalink
	 *
	 * @return array
	 */
	public function getPermalinkSqlFields()
	{
		return array(
			'year' => 'SUBSTRING(post_date_gmt, 1, 4)',
			'monthnum' => 'SUBSTRING(post_date_gmt, 6, 2)',
			'day' => 'SUBSTRING(post_date_gmt, 9, 2)',
			'hour' => 'SUBSTRING(post_date_gmt, 12, 2)',
			'minute' => 'SUBSTRING(post_date_gmt, 15, 2)',
			'second' => 'SUBSTRING(post_date_gmt, 18, 2)',
			'post_id' => 'ID', 
			'postname' => 'post_name',
			'author' => 'post_author',
		);
	}
	
	/**
	 * Determine whether the given post has any children posts
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return bool
	 */
	public function hasChildrenPosts(Fishpig_Wordpress_Model_Post $post)
	{
		$select = $this->_getReadAdapter()
			->select()
			->from($this->getMainTable(), 'ID')
			->where('post_parent=?', $post->getId())
			->where('post_type=?', $post->getPostType())
			->where('post_status=?', 'publish')
			->limit(1);
			
		return $this->_getReadAdapter()->fetchOne($select) !== false;
	}
	
/**
	 * Retrieve a collection of post comments
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return Fishpig_Wordpress_Model_Resource_Post_Comment_Collection
	 */
	public function getPostComments(Fishpig_Wordpress_Model_Post $post)
	{
		return Mage::getResourceModel('wordpress/post_comment_collection')
			->addPostIdFilter($post->getId())
			->addCommentApprovedFilter()
			->addParentCommentFilter(0)
			->addOrderByDate();
	}
	
	/**
	 * Retrieve the featured image for the post
	 *
	 * @param Fishpig_Wordpress_Model_Post $post
	 * @return Fishpig_Wordpress_Model_Image $image
	 */
	public function getFeaturedImage(Fishpig_Wordpress_Model_Post $post)
	{
		if ($images = $post->getImages()) {
			$select = $this->_getReadAdapter()
				->select()
				->from($this->getTable('wordpress/post_meta'), 'meta_value')
				->where('post_id=?', $post->getId())
				->where('meta_key=?', '_thumbnail_id')
				->limit(1);

			if (($imageId = $this->_getReadAdapter()->fetchOne($select)) !== false) {
				if (preg_match('/([a-z-]{1,})([0-9]{1,})/', $imageId, $matches)) {
					if (($prefix = trim($matches[1], '- ')) !== '') {
						$eventData = array(
							'object' => $post,
							'image_id' => $matches[2],
							'original_image_id' => $imageId,
							'result' => new Varien_Object(),
						);

						Mage::dispatchEvent('wordpress_post_get_featured_image_' . $prefix, $eventData);
						
						if ($eventData['result']->getFeaturedImage()) {
							return $eventData['result']->getFeaturedImage();
						}
					}
				}
				else {
					return Mage::getModel('wordpress/image')->load($imageId);
				}
			}
		}
		
		return false;
	}
}
