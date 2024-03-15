<?php

if(!defined( 'WPINC' )){
	die();
}

/**
 * Object Cache API
 *
 * @link https://developer.wordpress.org/reference/classes/wp_object_cache/
 *
 * @package WordPress
 * @subpackage Cache
 */

/** WP_Object_Cache class */
require_once $plugin_dir . '/main/objectcache.php';


function wp_cache_init() {
	$GLOBALS['wp_object_cache'] = WP_Object_Cache::get_instance();
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add( $key, $data, $group, (int) $expire );
}

function wp_cache_add_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->add_multiple( $data, $group, $expire );
}

function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set( $key, $data, $group, (int) $expire );
}

function wp_cache_set_multiple( array $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;

	return $wp_object_cache->set_multiple( $data, $group, $expire );
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;

	return $wp_object_cache->get( $key, $group, $force, $found );
}

function wp_cache_get_multiple( $keys, $group = '', $force = false ) {
	global $wp_object_cache;

	return $wp_object_cache->get_multiple( $keys, $group, $force );
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete( $key, $group );
}

function wp_cache_delete_multiple( array $keys, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->delete_multiple( $keys, $group );
}

function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->incr( $key, $offset, $group );
}

function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;

	return $wp_object_cache->decr( $key, $offset, $group );
}


function wp_cache_flush() {
	global $wp_object_cache;

	return $wp_object_cache->flush();
}

function wp_cache_flush_runtime() {
	return wp_cache_flush();
}

function wp_cache_flush_group( $group ) {
	global $wp_object_cache;

	return $wp_object_cache->flush_group( $group );
}

function wp_cache_supports( $feature ) {
	switch ( $feature ) {
		case 'add_multiple':
		case 'set_multiple':
		case 'get_multiple':
		case 'delete_multiple':
		case 'flush_runtime':
		case 'flush_group':
			return true;

		default:
			return false;
	}
}


function wp_cache_close() {
	return true;
}


function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;

	$wp_object_cache->add_global_groups( $groups );
}


function wp_cache_add_non_persistent_groups( $groups ) {
	// Default cache doesn't persist so nothing to do here.
}

function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;

	$wp_object_cache->switch_to_blog( $blog_id );
}

function wp_cache_reset() {
	_deprecated_function( __FUNCTION__, '3.5.0', 'wp_cache_switch_to_blog()' );

	global $wp_object_cache;

	$wp_object_cache->reset();
}


#[AllowDynamicProperties]
class WP_Object_Cache {

	private $cache = array();
	public $cache_hits = 0;
	public $cache_misses = 0;
	protected $global_groups = array();
	private $blog_prefix;
	private $multisite;
	public $object_cache;
	static $instance;

	public function __construct() {
		$this->object_cache = \SpeedyCache\ObjectCache::get_instance();

		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? get_current_blog_id() . ':' : '';
	}
	
	static function get_instance(){
		if(self::$instance){
			return self::$instance;
		}
		
		self::$instance = new self;
		
		return self::$instance;
	}

	public function __get( $name ) {
		return $this->$name;
	}

	public function __set( $name, $value ) {
		return $this->$name = $value;
	}

	public function __isset( $name ) {
		return isset( $this->$name );
	}

	public function __unset( $name ) {
		unset( $this->$name );
	}

	protected function is_valid_key( $key ) {

		if ( is_int( $key ) ) {
			return true;
		}

		if ( is_string( $key ) && trim( $key ) !== '' ) {
			return true;
		}

		$type = gettype( $key );

		if ( ! function_exists( '__' ) ) {
			wp_load_translations_early();
		}

		$message = is_string( $key )
			? __( 'Cache key must not be an empty string.' )
			/* translators: %s: The type of the given cache key. */
			: sprintf( __( 'Cache key must be integer or non-empty string, %s given.' ), $type );

		_doing_it_wrong(
			sprintf( '%s::%s', __CLASS__, debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 )[1]['function'] ),
			$message,
			'6.1.0'
		);

		return false;
	}

	protected function _exists( $key, $group ) {
		if(!$id = $this->id($key, $group)){
			return false;
		}
		
		return isset($this->cache[$group][$id]);
	}

	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if(wp_suspend_cache_addition()){
			return false;
		}

		if(! $this->is_valid_key($key)){
			return false;
		}

		if($this->_exists($key, $group)){
			return false;
		}

		return $this->set($key, $data, $group, (int) $expire);
	}

	public function add_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->add( $key, $value, $group, $expire );
		}

		return $values;
	}

	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}
		
		if(!$id = $this->id($key, $group)){
			return false;
		}

		if (!$this->_exists($key, $group)){
			if(!$this->object_cache::exists($id)){
				return false;
			}
		}

		return $this->set( $key, $data, $group, (int) $expire );
	}

	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if(! $this->is_valid_key($key)){
			return false;
		}

		if(is_object($data)){
			$data = clone $data;
		}

		if(!$id = $this->id($key, $group)){
			return false;
		}

		if(!in_array($group, $this->object_cache::$non_cache_group)){
			$this->object_cache::set($id, $data, (int) $expire);
		}

		$this->cache[$group][$id] = $data;

		return true;
	}

	public function set_multiple( array $data, $group = '', $expire = 0 ) {
		$values = array();

		foreach ( $data as $key => $value ) {
			$values[ $key ] = $this->set( $key, $value, $group, $expire );
		}

		return $values;
	}

	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		if ( ! $this->is_valid_key( $key ) ) {
			return false;
		}
		
		if(!$id = $this->id($key, $group)){
			$found = false;
			$this->cache_misses += 1;
			return false;
		}
		
		// In memory cache
		if($this->_exists($key, $group)){
			$found = true;
			$this->cache_hits += 1;
			return $this->cache[$group][$id];
		}

		// Non Cache Group check.
		if(empty($this->object_cache) || in_array($group, $this->object_cache::$non_cache_group)){
			$found = false;
			$this->cache_misses += 1;
			return false;
		}
		
		// Fetch cache from redis.
		if($cache = $this->object_cache::get($id)){
			$found = true;
			$this->cache_hits += 1;
			
			if(is_serialized($cache)){
				$cache = maybe_unserialize($cache);
			}

			$this->cache[$group][$id] = $cache;
			return $this->cache[$group][$id];
		}

		$found = false;
		$this->cache_misses += 1;
		return false;
	}

	public function get_multiple($keys, $group = 'default', $force = false) {
		$values = array();

		foreach($keys as $key){
			$values[$key] = $this->get($key, $group, $force);
		}

		return $values;
	}

	public function delete($key, $group = 'default', $deprecated = false) {
		if (! $this->is_valid_key($key)){
			return false;
		}

		if(!$id = $this->id($key, $group)){
			return false;
		}

		if(empty($this->cache[$group]) && empty($this->cache[$group][$id]) && !$this->object_cache::exists($id)) {
			return false;
		}

		unset($this->cache[$group][$id]);
		$this->object_cache::delete($id);
		return true;
	}

	public function delete_multiple(array $keys, $group = ''){
		$values = array();

		foreach($keys as $key){
			$values[$key] = $this->delete($key, $group);
		}

		return $values;
	}

	public function incr($key, $offset = 1, $group = 'default'){
		return $this->incr_desr($key, $offset = 1, $group, true);
	}

	public function decr($key, $offset = 1, $group = 'default'){
		return $this->incr_desr($key, $offset = 1, $group, false);
	}
	
	public function incr_desr($key, $offset = 1, $group = 'default', $incr = true){
		if (! $this->is_valid_key($key)){
			return false;
		}

		$cache_val = $this->get($key, $group);

		if(false === $cache_val){
			return false;
		}

		if(! is_numeric( $cache_val)){
			$cache_val = 0;
		}

		$offset = (int) $offset;

		if($incr){
			$cache_val += $offset;
		}
		else {
			$cache_val -= $offset;
		}

		if ( $cache_val < 0 ) {
			$cache_val = 0;
		}

		$this->set($key, $cache_val, $group);

		return $cache_val;
	}

	public function flush() {
		$this->cache = array();

		if(!empty($this->object_cache)){
			$this->object_cache::flush_db();
		}

		return true;
	}

	public function flush_group($group) {
		unset($this->cache[$group]);

		return true;
	}

	public function add_global_groups($groups){
		$groups = (array) $groups;

		$groups = array_fill_keys($groups, true);
		$this->global_groups = array_merge($this->global_groups, $groups);
	}
	
	public function is_global($group){
		return in_array($group, $this->global_groups);
	}

	public function switch_to_blog($blog_id){
		$blog_id = (int) $blog_id;
		$this->blog_prefix = $this->multisite ? $blog_id . ':' : '';
	}

	public function reset(){
		_deprecated_function(__FUNCTION__, '3.5.0', 'WP_Object_Cache::switch_to_blog()');

		// Clear out non-global caches since the blog ID has changed.
		foreach(array_keys($this->cache) as $group){
			if(! isset( $this->global_groups[$group])){
				unset($this->cache[$group]);
			}
		}
	}

	public function stats() {
		echo '<p>';
		echo "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
		echo "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
		echo '</p>';
		echo '<ul>';
		foreach($this->cache as $group => $cache){
			echo '<li><strong>Group:</strong> ' . esc_html($group) . ' - ( ' . number_format(strlen(serialize($cache)) / KB_IN_BYTES, 2 ) . 'k )</li>';
		}
		echo '</ul>';
	}
	
	public function id($key, $group){
		$prefix = $this->is_global($group) ? '' : $this->blog_prefix;
		
		if(empty($group)){
			$group = 'default';
		}

		return  $prefix . $group . '.' . $key;
	}
}