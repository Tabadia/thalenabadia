<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class ObjectCache{
	static $oc_driver = 'Redis';
	static $port = '6379';
	static $host = '127.0.0.1';
	static $conn;
	static $ttl = 360; // in seconds
	static $is_multisite = false;
	static $blog_id;
	static $prefix = 'speedycache';
	static $conf_file = WP_CONTENT_DIR . '/.speedycache-object.dat';
	static $conf;
	static $instance;
	static $persistent;
	static $async_flush;
	static $serialize;
	static $non_cache_group;
	
	static function get_instance(){
		if(self::$instance){
			return self::$instance;
		}

		self::$instance = new self();
		
		try{
			self::boot();
		} catch(\Exception $e){
			// Don't need to log here as the error has been already logged before, this it just to prevent error
		}

		return self::$instance;
	}

	static function boot(){
		self::$conf = self::get_conf();

		self::$host = !empty(self::$conf['host']) ? self::$conf['host'] : '127.0.0.1';
		self::$port = !empty(self::$conf['port']) ? self::$conf['port'] : '6379';
		self::$ttl = !empty(self::$conf['ttl']) ? (int) self::$conf['ttl'] : 360;
		self::$persistent = !empty(self::$conf['persistent']) ? true : false;
		self::$async_flush = !empty(self::$conf['async_flush']) ? true : false;
		self::$serialize = !empty(self::$conf['serialization']) ? self::$conf['serialization'] : 'none';
		$compress = !empty(self::$conf['compress']) ? self::$conf['compress'] : 'COMPRESSION_NONE';
		self::$non_cache_group = (!empty(self::$conf['non_cache_group']) && is_array(self::$conf['non_cache_group'])) ? self::$conf['non_cache_group'] : [];
		
		if(self::$host === 'localhost'){
			self::$host = '127.0.0.1';
		}
		
		if(empty(self::connect())){
			error_log('SpeedyCache: Unable to connect to Redis');
			return false;
		}
		
		try{
			
			switch(self::$serialize){
				case 'SERIALIZER_PHP':
					self::$conn->setOption(self::$conn::OPT_SERIALIZER, self::$conn::SERIALIZER_PHP);
					break;
					
				case 'SERIALIZER_IGBINARY':
					self::$conn->setOption(self::$conn::OPT_SERIALIZER, self::$conn::SERIALIZER_IGBINARY);
					break;
					
				default:
					self::$conn->setOption(self::$conn::OPT_SERIALIZER, self::$conn::SERIALIZER_NONE);
					break;
			}

			switch($compress){
				case 'COMPRESSION_NONE':
					self::$conn->setOption(self::$conn::OPT_COMPRESSION, self::$conn::COMPRESSION_NONE);
					break;
					
				case 'COMPRESSION_ZSTD':
					self::$conn->setOption(self::$conn::OPT_COMPRESSION, self::$conn::COMPRESSION_ZSTD);
					self::$conn->setOption(self::$conn::OPT_COMPRESSION_LEVEL, (string) -5);
					break;
					
				case 'COMPRESSION_LZ4':
					self::$conn->setOption(self::$conn::OPT_COMPRESSION, self::$conn::COMPRESSION_LZ4);
					break;

				case 'COMPRESSION_LZF':
					self::$conn->setOption(self::$conn::OPT_COMPRESSION, self::$conn::COMPRESSION_LZF);
					break;
					
			}

		} catch(\RedisException $e){
			error_log($e->getMessage());
			throw new \Exception($e->getMessage()); // To show on settings page
			return false;
		}

	}
	
	static function get_conf(){
		if(!file_exists(self::$conf_file)){
			return [];
		}

		$conf = file_get_contents(self::$conf_file);
		
		if(empty($conf)){
			error_log('SpeedyCache: Conf file was empty');
			return;
		}
		
		return json_decode($conf, true);
	}
	
	// Creates a unique id based on blogID, group and key
	static function id($key, $group){
		return self::$prefix  . ':' . self::$blog_id.$group . ':' . $key;
	}
	
	// Updates object-cache.php file at wp-content/object-cache.php
	static function update_file(){
		$file = WP_CONTENT_DIR . '/object-cache.php';
		
		if(!file_exists($file)){
			touch($file);
		}
		
		$code = '<?php

if(!defined("WPINC")){
	die();
}

if(!defined("SPEEDYCACHE_OBJECT_CACHE")){
	define("SPEEDYCACHE_OBJECT_CACHE", true);
}

$plugin_dir = (defined("WP_PLUGIN_DIR") ? WP_PLUGIN_DIR : WP_CONTENT_DIR . "/plugins") . "/speedycache-pro";
$conf_file = WP_CONTENT_DIR . "/.speedycache-object.dat";
$lib_file = $plugin_dir . "/main/object-cache-lib.php";


if(file_exists($plugin_dir) && file_exists($conf_file) && file_exists($lib_file)){
	$spdf_config = file_get_contents($conf_file);
	$spdf_config = json_decode($spdf_config, true);
	
	if (! SPEEDYCACHE_OBJECT_CACHE || (empty($spdf_config["admin"]) && defined("WP_ADMIN"))){
		wp_using_ext_object_cache(false);
	}else if (file_exists($lib_file)) {
		include_once $lib_file;
	}
}';
		file_put_contents($file, $code);

	}
	
	static function connect(){

		if(!empty(self::$conn)){
			return true;
		}
		
		if(!class_exists(self::$oc_driver)){
			error_log('SpeedyCache: The defined driver ' . self::$oc_driver . 'not present');
			return false;
		}
		
		$failed = false;
		$is_socket = false;
		
		if(strpos(self::$host, '.sock')){
			$is_socket = true;
		}

		$persistent = !empty(self::$persistent) ? 'pconnect' : 'connect';

		if(self::$oc_driver == 'Redis'){

			try {
				self::$conn = new \Redis();
				
				if($is_socket){
					self::$conn->{$persistent}(self::$host);
				} else {
					self::$conn->{$persistent}(self::$host, self::$port);
				}
				
				if(!empty(self::$conf['username']) && !empty(self::$conf['password'])){
					self::$conn->auth(['user' => self::$conf['username'], 'pass' => self::$conf['password']]);
				} else if(!empty(self::$conf['password'])){
					self::$conn->auth(self::$conf['password']);
				}

				// Testing if connection worked
				$res = self::$conn->ping();
				
				if(empty($res) && $res !== '+PONG'){
					$failed = true;
				}
				
				if(!empty($failed)){
					self::$conn = null;
					return false;
				}

				self::$conn->select((int) (!empty(self::$conf['db-id']) ? self::$conf['db-id'] : 0));

			}catch(\RedisException $e){
				error_log('SpeedyCache' . $e->getMessage());
				throw new \Exception($e->getMessage()); // To show on settings page
				return false;
			}
			catch(\Exception $e){
				error_log('SpeedyCache  ->>' . $e->getMessage());
				throw new \Exception($e->getMessage()); // To show on settings page
			}
		}
		
		return true;

	}
	
	static function set($key, $data, $expire = 0){
		
		if(empty(self::$conn)){
			return false;
		}

		$ttl = $expire ?: self::$ttl;
		
		if((is_array($data) || is_object($data)) && self::$serialize === 'none'){
			$data = serialize($data);
		}

		try{
			$res = self::$conn->setEx($key, $ttl, $data);
		} catch (\RedisException $ex) {
			error_log($ex->getMessage());
		}
	}
	
	static function get($key){
		
		if(empty(self::$conn)){
			return false;
		}

		try{
			return self::$conn->get($key);
		}catch(\RedisException $e){
			error_log($e->getMessage());
			return false;
		}
	}
	
	static function exists($key){
		
		if(empty(self::$conn)){
			return false;
		}
		
		try{
			return self::$conn->exists($key);
		}catch(\RedisException $e){
			error_log($e->getMessage());
			return false;
		}
	}
	
	static function delete($key){
		
		$del = self::$async_flush ? 'unlink' : 'del';

		if(empty(self::$conf['enable'])){
			return false;
		}
		
		if(empty(self::$conn)){
			return false;
		}

		try{
			self::$conn->{$del}($key);
		} catch(\RedisException $e){
			error_log($e->getMessage());
			return false;
		}
	}
	
	static function get_memory(){
		self::boot();
		
		if(empty(self::$conn)){
			return 'None';
		}
		
		try{
			$memory = self::$conn->info('memory');
		} catch(\RedisException $e){
			error_log($e->getMessage());
			return 'None';
		}
		
		if(!empty($memory['used_memory'])){
			return size_format($memory['used_memory'], 2);
		}

		return 'None';
	}
	
	// Flushes whole database
	static function flush_db($sync = true){
		if(empty(self::$conf['enable'])){
			return false;
		}

		if(empty(self::$conn)){
			return false;
		}
		
		try{
			return self::$conn->flushDb($sync);
		} catch(\RedisException $e){
			error_log($e->getMessage());
			return false;
		}
	}

}


