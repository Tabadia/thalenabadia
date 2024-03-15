<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

class Image{

	static function init(){
		global $speedycache, $speedycache_optm_method;
		
		$speedycache->image = array();
		$speedycache->image['upload_dir'] = wp_upload_dir();
		$speedycache->image['id'] = false;
		$speedycache->image['metadata'] = array();
		$speedycache->image['name'] = '';
		$speedycache->image['path'] = '';
		$speedycache->image['url'] = '';
		$speedycache->image['images'] = array();
		$speedycache->image['images_clone'] = array();
		$speedycache->image['disabled_method'] = 0;
		
		// Default settings for img optimization
		$speedycache->image['settings'] = array(
			'on_upload' => false,
			'automatic_optm' => false,
			'url_rewrite' => false,
			'compression_method' => 'gd',
			'compression_quality' => '70'
		);
		
		$speedycache_optm_method = array(
			'gd' => array(
				'title' => 'GD',
				'desc' => 'Compress using PHP\'s native Extension for compression and conversion.'
			),
			'imagick' => array(
				'title' => 'Imagick',
				'desc' => 'Imagick outputs better image quality after compression at the cost of a little bigger file size compared to GD.'
			),
			'cwebp' => array(
				'title' => 'cWebP',
				'desc' => 'cwebp is a utility which can be downloaded on your server and its fast and light on you server.'
			)
		);
		
		// Binaries for cwebp
		$speedycache->image['binaries'] = array(
			'WINNT' => ['cwebp-122-windows-x64.exe', 'gif2webp-122-windows-x64.exe'],
			'Linux' => ['cwebp-122-linux-x86-64', 'gif2webp-122-linux-x86-64']
		);
		
		if(array_key_exists(PHP_OS, $speedycache->image['binaries'])){
			$speedycache->image['cwebp_binary'] = $speedycache->image['binaries'][PHP_OS][0];
			$speedycache->image['cwebp_gif'] = $speedycache->image['binaries'][PHP_OS][1];
		}

		if($img_settings = get_option('speedycache_img')){
			$speedycache->image['settings'] = array_merge($speedycache->image['settings'], $img_settings);
			self::compression_method_checks();

			return;
		}
		
		self::compression_method_checks();
		update_option('speedycache_img', $speedycache->image['settings']);

	}

	static function total_reduction(){
		global $wpdb;
		
		$query = "SELECT sum(`meta_value`) as total FROM `".$wpdb->prefix."postmeta` WHERE `meta_key`= 'speedycache_optimisation_reduction'";
		$result = $wpdb->get_row( $query );
		
		if(!empty($result->total)){
			$reduced = ($result->total && $result->total > 0) ? $result->total : 0;
			
			return $reduced > 10000 ? $reduced/1000 : $reduced;	
		}
		
		return 0;
	}

	static function optimized_file_count(){
		global $wpdb;
		
		$query = "SELECT count(`meta_value`) as optimized FROM `".$wpdb->prefix."postmeta` WHERE `meta_key`= 'speedycache_optimisation'";
		$result = $wpdb->get_row($query);
		
		if($result->optimized && $result->optimized > 0){
			return $result->optimized;
		}
		
		return 0;
	}

	static function optm_img_count(){

		return self::count_query(array(
			'post_type' => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif'),
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'speedycache_optimisation',
					'compare' => 'EXISTS'
				),
				array(
					'key' => 'speedycache_optimisation',
					'value' => base64_encode('"destination_path"'),
					'compare' => 'LIKE'
				)
			)
		));
	}

	static function error_count(){

		return self::count_query(array(
			'post_type' => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif'),
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'speedycache_optimisation',
					'compare' => 'EXISTS'
				),
				array(
					'key' => 'speedycache_optimisation',
					'value' => base64_encode('"error_code"'),
					'compare' => 'LIKE'
				)
			)
		));
	}

	static function null_posts_groupby(){
		return '';
	}

	static function count_posts_fields(){
		return 'COUNT(*) as post_count_speedycache';
	}

	static function count_query($query_images_args){
		add_filter('posts_fields', '\SpeedyCache\Image::count_posts_fields');
		add_filter('posts_groupby', '\SpeedyCache\Image::null_posts_groupby');

		unset($query_images_args['offset']);
		unset($query_images_args['order']);
		unset($query_images_args['orderby']);

		$query_images_args['posts_per_page'] = -1;

		$query_image = new \WP_Query( $query_images_args );

		return $query_image->posts[0]->post_count_speedycache;
	}

	static function image_count(){

		return self::count_query(array(
			'post_type' => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif'),
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => '_wp_attachment_metadata',
					'compare' => 'EXISTS'
				)
			)
		));
	}

	static function uncompressed_count(){

		return self::count_query(array(
			'post_type' => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif'),
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'speedycache_optimisation',
					'compare' => 'NOT EXISTS'
				)
			)
		));
	}

	static function unoptimized(){
		global $speedycache;
		
		$tmp_image = array();

		$optimization_data = get_post_meta($speedycache->image['id'], 'speedycache_optimisation', true);
		$optm_json = base64_decode($optimization_data);
		
		if(empty($optm_json)){
			$percentage = 100 / count($speedycache->image['images']);
			
			return array('meta_optimized' => array(), 'images' => $speedycache->image['images'], 'total_reduction' => 0, 'percentage' => $percentage);
		}

		$optm_json = json_decode($optm_json, true);
		$meta_optimized = self::object_to_array($optm_json);
		$percentage = count($meta_optimized) * 100/count($speedycache->image['images']);
		
		foreach($speedycache->image['images'] as $key => $value){
			$exist = false;

			foreach($meta_optimized as $meta_key => $meta_value){
				if($value['file'] == $meta_value['file']){
					$exist = true;
					//break;
				}
			}

			if(empty($exist)){
				array_push($tmp_image, $value);
			}
		}

		//START: total reduction
		$total_reduction = 0;
		
		foreach($meta_optimized as $m_key => $m_value){
			$m_value['reduction'] = isset($m_value['reduction']) ? $m_value['reduction'] : 0;
			
			if($m_key == 0){
				$reduction = $m_value['reduction'];
			}
			
			$total_reduction += $m_value['reduction'];
		}
		//END: total reduction

		if(count($tmp_image) <= 0){
			return array('meta_optimized' => array(), 'images' => array(), 'total_reduction' => 0);
		}

		$last = speedycache_optget('last');
		if(!empty($last)){
			if(preg_match('/last-(\d+)/', $last, $last_number)){
				if(count($tmp_image) > 5){
					$tmp_image = array_slice($tmp_image, $last_number[1]*-1, 1);
				}
			}
		}

		return array('meta_optimized' => $meta_optimized, 'images' => array_slice($tmp_image, 0, 1), 'total_reduction' => $total_reduction, 'percentage' => $percentage);
	}

	static function object_to_array($obj){
		if(is_object($obj)){
			$obj = (array) $obj;
		}
		
		if(!is_array($obj)){
			$new = $obj;
			return $new;
		}
		
		$new = array();
		foreach($obj as $key => $val){
			$new[$key] = self::object_to_array($val);
		}
		return $new;
	}

	static function reorder_by_dimensions(){
		global $speedycache;
		
		$tmp = $speedycache->image['images'];

		foreach($tmp as $key => $value){
			$width_list[$key] = $value['width'];
		}

		array_multisort($width_list, SORT_DESC, $tmp);
		
		return $tmp;
	}

	static function optimize_single($id = null){
		global $speedycache;
		
		if(!empty($id)){
			self::init();
		}
		
		self::set_id($id);
		self::set_meta_data();

		
		if(wp_next_scheduled('speedycache_auto_optm', array($speedycache->image['id']))){
			wp_clear_scheduled_hook('speedycache_auto_optm' , array($speedycache->image['id']));
		}

		if(empty($speedycache->image['id'])){
			return array('finish', 'success'); 
		}
		
		if(!isset($speedycache->image['metadata']['file']) && !empty($speedycache->image['id'])){
			$meta_optimized = array();
			$meta_optimized[0]['time'] = time();
			$meta_optimized[0]['id'] = $speedycache->image['id'];
			$meta_optimized[0]['error_code'] = 17;

			update_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction', 0);
			update_post_meta($speedycache->image['id'], 'speedycache_optimisation', base64_encode(json_encode($meta_optimized)));

			return array('Image has been optimized', 'success', $speedycache->image['id'], 100);
		}

		self::set_name();
		self::set_path();
		self::set_url();
		self::set_images();

		if(!empty($speedycache->image['id']) && count($speedycache->image['images']) == 0){
			$meta_optimized = array();
			$meta_optimized[0]['time'] = time();
			$meta_optimized[0]['id'] = $speedycache->image['id'];
			$meta_optimized[0]['error_code'] = 18;

			update_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction', 0);
			update_post_meta($speedycache->image['id'], 'speedycache_optimisation', base64_encode(json_encode($meta_optimized)));

			return array('Image has been optimized', 'success', $speedycache->image['id'], 100);
		}
		
		$error_exist = false;
		$meta_optimized = array();
		$total_reduction = 0;
		
		$speedycache->image['images'] = self::unique_array($speedycache->image['images']);
		$speedycache->image['images'] = self::reorder_by_dimensions();

		$speedycache->image['images_clone'] = $speedycache->image['images'];

		$unoptimized = self::unoptimized();
		$speedycache->image['images'] = $unoptimized['images'];
		$meta_optimized = $unoptimized['meta_optimized'];
		$total_reduction = $unoptimized['total_reduction'];
		$percentage = isset($unoptimized['percentage']) && $unoptimized['percentage'] ? $unoptimized['percentage'] : 0;

		if(count($speedycache->image['images']) == 0){
			return array('Image has been optimized', 'success', '', 100);
		}

		foreach($speedycache->image['images'] as $key => $value){
		
			$res = self::compress($value);
			
			if(!empty($res['success'])){
				$value['destination_path'] = $res['destination_path'];
				$value['reduction'] = $res['reduction'];

				$total_reduction += $value['reduction'];

				$value['time'] = time();
				$value['id'] = $speedycache->image['id'];
				
				array_push($meta_optimized, $value);
			}else{
				if(!isset($res['error_code']) && isset($res['error_message'])){
					return array($res['error_message'], 'error');
					break;
				}

				if(in_array($res['error_code'] , array(2, 6, 7, 11, 19, 20, 22))){
					return array($res['error_message'], 'error');
					break;
				}

				$value['error_code'] = $res['error_code'];
				$error_exist = true;
			}

			$value['time'] = time();
			$value['id'] = $speedycache->image['id'];

			if(!empty($value['error_code'])){
				if($value['error_code'] != 8 || ($value['error_code'] == 8 && $key === 0)){
					array_push($meta_optimized, $value);
				}
			}
		}
		
		$percentage = self::update_meta($total_reduction, $meta_optimized);

		return array('Image has been optimized', 'success', $speedycache->image['id'], $percentage, $total_reduction);
	}

	static function update_meta($total_reduction, $meta_optimized){
		global $speedycache;
		
		if(isset($meta_optimized[0]) && isset($meta_optimized[0]['error_code']) && $meta_optimized[0]['error_code']){

			update_post_meta($speedycache->image['id'], 'speedycache_optimisation', base64_encode(json_encode($meta_optimized)));
			update_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction', 0);

			return 100;
		}

		$percentage = 0;
		$meta_temp = array();
		
		foreach($speedycache->image['images_clone'] as $key => $value){
			$backup_file = $value['file'];
			
			$value['file'] = preg_replace('/.(jpg|jpeg|png|gif)$/', '.webp', $value['file']);
			
			if(!file_exists($value['file']) || !file_exists($backup_file)){
				continue;
			}

			$diff = filesize($backup_file) - filesize($value['file']);
			$diff = $diff > 0 ? $diff : 0;

			$value['destination_path'] = $backup_file;
			$value['reduction'] = $diff;
			$value['time'] = time();
			$value['id'] = $speedycache->image['id'];

			array_push($meta_temp, $value);
		}

		foreach($meta_optimized as $m_key => $m_value){
			if(empty($m_value['error_code'])){
				continue;
			}

			$exist = false;

			for($i=0; $i < count($meta_temp); $i++){
				if($meta_temp['file'] == $m_value['file']){
					$exist = true;
				}
			}

			if(empty($exist)){
				$m_value['destination_path'] = '';
				$m_value['reduction'] = 0;
				$m_value['time'] = time();
				$m_value['id'] = $speedycache->image['id'];

				array_push($meta_temp, $m_value);
			}
		}
		
		if(count($meta_temp) > 0){
			$percentage = count($meta_temp)*100/count($speedycache->image['images_clone']);
		}else{
			$percentage = 0;
		}

		update_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction', $total_reduction);
		update_post_meta($speedycache->image['id'], 'speedycache_optimisation', base64_encode(json_encode($meta_temp)));

		return $percentage;
	}

	static function compress($source_image){
		global $speedycache;

		/*
			Error Codes
			2 = in backup folder parent folder not writable
			3 = no need to optimize
			4 = source is not writable
			5 = destination is not writable
			6 = ImageMagick library is not avaliable
			7 = Error via api
			8 = Source file does not exist
			9 = Image size exceed 5mb limit while processing
		   11 = Empty Name
		   12 = Forbidden
		   13 = CloudFlare to restrict access
		   14 = No Extension
		   15 = Image size is 0Kb
		   16 = Corrupted Image
		   17 = Empty Metadata
		   18 = No Image
		   19 = webp is not saved
		   20 = file size of destination_move_source_path is zero
		   21 = Unacceptable file type
		   22 = Unable to Convert Image using cwebp.
		*/

		// if the url starts with /wp-content
		if(preg_match('/^\/' . SPEEDYCACHE_WP_CONTENT_DIR . '/i', $source_image['url'])){
			$source_image['url'] = home_url().$source_image['url'];
		}

		$source_path = $source_image['file'];
		$res_backup = array('success' => true, 'error_message' => '');
		$webp_path = preg_replace('/.(jpe?g|png|gif)$/', '.webp', $source_path);

		if(strlen($speedycache->image['name']) === 0){
			return array('success' => false, 'error_code' => 11);
		}

		if(!file_exists($source_path)){
			return array('success' => false, 'error_code' => 8);
		}

		if(!pathinfo($source_image['url'], PATHINFO_EXTENSION)){
			return array('success' => false, 'error_code' => 14);
		}

		if(@filesize($source_path) > 5000000){
			return array("success" => false, 'error_code' => 9);
		}

		if(!self::allowed_mime($source_path)){
			return array('success' => false, 'error_code' => 21);
		}

		if(!self::path_is_image($source_path)){
			return array('success' => false, 'error_code' => 16);
		}

		if(filesize($source_path) == 0){
			return array('success' => false, 'error_code' => 15);
		}

		if(@rename($source_path, $source_path.'_writabletest')){
			rename($source_path.'_writabletest', $source_path);
		}else{
			return array('success' => false, 'error_message' => $source_path . ' is not writable', 'error_code' => 4);
		}

		$optm_result = self::start_optimization($source_path); // here we need to plugin compression static function
		
		if(empty($optm_result['success'])){
			//NOTE:Place some error Message here.
			return $optm_result;
		}

		if(!file_exists($webp_path)){
			return array('success' => false, 'error_code' => 19, 'error_message' => $webp_path . ' destination_path is not saved');
		}
		
		if(filesize($webp_path) <= 0){
			return array('success' => false, 'error_code' => 20, 'error_message' => $webp_path . ' file size of destination_path is zero');
		}

		$diff = self::compare_sizes($source_path, $webp_path);
		return array('success' => true, 'destination_path' => $webp_path, 'reduction' => $diff);
	}


	static function start_optimization($img){
		global $speedycache;

		switch($speedycache->image['settings']['compression_method']){
			case 'gd': 
				return self::gd_webp($img);
				
			case 'imagick':
				return self::imagick_webp($img);
				
			case 'cwebp':
				if(defined('SPEEDYCACHE_PRO')){					
					return \SpeedyCache\Image::cwebp_convert($img);
				}
				
				return array('success' => false, 'error_message' => 'SpeedyCache cwebp is a Pro feature');
				
			default:
				return array('success' => false,  'error_message' => 'The provided conversion method is not valid');
		}
	}

	static function path_is_image($source_path){
		$size = getimagesize($source_path);

		if(empty($size)){
			return false;
		}

		return true;
	}

	static function get_quality($img){
		$dimensions = $img->getImageGeometry();
		
		if($dimensions['width'] < 200 && $dimensions['height'] < 200){
			return 85;
		}

		return 90;
	}

	static function compare_sizes($source_path, $destination_path){
		$diff = filesize($source_path) - filesize($destination_path);

		return ($diff > 0) ? $diff : 1;
	}


	//TODO:: Will need it when we will add non webp compression
	static function create_backup_folder($destination_path){
		global $speedycache;
		
		$destination_path = str_replace($speedycache->image['upload_dir']['basedir'], '', $destination_path);
		$path_arr = explode('/', $destination_path);

		$path = $speedycache->image['upload_dir']['basedir'];

		for ($i=1; $i < count($path_arr) - 1; $i++){
			$parent_path = $path;
			$path = $path.'/'.$path_arr[$i];

			if(is_dir($path)){
				continue;
			}
			
			if(@mkdir($path, 0755, true)){
				//
			}else{
				//warning
				if($path_arr[$i] == 'speedycache-backup'){
					//toDO: to stop cron job and warn the user
				}

				return array('success' => false, 'error_message' => $parent_path.' is not writable', 'error_code' => 2);
			}
		}

		return array('success' => true, 'error_message' => '');
	}

	static function set_id($id = null){
		global $speedycache;
		
		$get_id = speedycache_optget('id');
		
		if(!empty($get_id)){
			$speedycache->image['id'] = intval($get_id);
		}elseif(!empty($id)){
			$speedycache->image['id'] = intval($id);
		}else{
			$speedycache->image['id'] = self::get_first_id();
		}
	}

	static function set_images(){
		global $speedycache;
		
		if(empty($speedycache->image['metadata']['file'])){
			return;
		}

		$arr = array(
			'file' => $speedycache->image['upload_dir']['basedir'].'/'.$speedycache->image['metadata']['file'],
			'url' => $speedycache->image['upload_dir']['baseurl'].'/'.$speedycache->image['metadata']['file'],
			'width' => $speedycache->image['metadata']['width'],
			'height' => $speedycache->image['metadata']['height'],
			'mime_type' => ''
		);
		
		array_push($speedycache->image['images'], $arr);

		$i = 0;
		$image_error = false;

		if(!is_array($speedycache->image['metadata']['sizes'])){
			if(empty($image_error)){
				self::not_in_metadata();
			}
		}

		foreach((array)$speedycache->image['metadata']['sizes'] as $key => $value){
			$value['url'] = $speedycache->image['url'].$value['file'];
			$value['file'] = $speedycache->image['path'].$value['file'];
			$value['mime_type'] = isset($value['mime-type']) ? $value['mime-type'] : '';

			unset($value['mime-type']);

			if($i == 0){
				if(self::not_found(self::get_correct_url($speedycache->image['upload_dir']['baseurl'].'/'.$speedycache->image['metadata']['file']))){
					$image_error = true;
					break;
				}
			}

			if(!self::not_found(self::get_correct_url($value['url'])) && self::allowed_mime($value['file'])){
				array_push($speedycache->image['images'], $value);
			}

			$i++;
		}

		if(empty($image_error)){
			self::not_in_metadata();
		}
	}

	static function get_correct_url($path){
		if(preg_match('/^\/'.SPEEDYCACHE_WP_CONTENT_DIR.'/i', $path)){
			
			// content_url() must return HTTP but it return /wp-content so we need to check
			if(content_url() == '/'.SPEEDYCACHE_WP_CONTENT_DIR && home_url() == site_url()){
				$path = home_url().$path;
			}
		}

		return $path;
	}

	static function not_in_metadata(){
		global $speedycache;
		
		$paths = array();

		foreach($speedycache->image['images'] as $key => $value){
			array_push($paths, $value['file']);
		}
		
		$files = glob($speedycache->image['path'].$speedycache->image['name'].'-'.'*');

		foreach((array)$files as $file){
			if(@filesize($file) > 1000000){
				continue;
			}

			if(!preg_match('/\.(jpg|jpeg|jpe|png|gif)$/i', $file)){
				continue;
			}

			$exp_dos = explode('/',$file);
			$basename = end($exp_dos);

			if(in_array($file, $paths)){
				continue;
			}
			
			if(!preg_match('/'.preg_quote($speedycache->image['name'], '/').'-(\d+)x(\d+)\..+/', $basename, $dimensions)){
				continue;
			}
			
			$value = array(
				'url' => $speedycache->image['url'].$basename,
				'file' => $file,
				'width' => $dimensions[1],
				'height' => $dimensions[2],
			);

			if(!self::not_found($value['url'])){
				array_push($speedycache->image['images'], $value);
			}
		}
	}

	static function set_path(){
		global $speedycache;
		
		$speedycache->image['path'] = $speedycache->image['upload_dir']['basedir'].'/'.preg_replace('/'.preg_quote($speedycache->image['name'], '/').'.+/', '', $speedycache->image['metadata']['file']);
	}

	static function set_url(){
		global $speedycache;
		
		$speedycache->image['url'] = $speedycache->image['upload_dir']['baseurl'].'/'.preg_replace('/'.preg_quote($speedycache->image['name'], '/').'.+/', '', $speedycache->image['metadata']['file']);
	}

	static function set_name(){
		global $speedycache;
		
		if(empty($speedycache->image['metadata'])){
			return;
		}

		if(isset($speedycache->image['metadata']['sizes']) && count($speedycache->image['metadata']['sizes']) > 0){
			$array_values = array_values($speedycache->image['metadata']['sizes']);
			$speedycache->image['name'] = preg_replace('/-'.$array_values[0]['width'].'x'.$array_values[0]['height'].'.+/', '', $array_values[0]['file']);

			if(!$speedycache->image['name']){
				$speedycache->image['name'] = substr($speedycache->image['metadata']['file'], strrpos($speedycache->image['metadata']['file'], '/') + 1);
			}
			
			return;
		}

		$info = pathinfo($speedycache->image['metadata']['file']);
		$speedycache->image['name'] =  basename($speedycache->image['metadata']['file'],'.'.$info['extension']);

		//$this->name = substr($this->metadata['file'], strrpos($this->metadata['file'], '/') + 1);
	}

	static function set_meta_data(){
		global $speedycache;
		
		$speedycache->image['metadata'] = wp_get_attachment_metadata($speedycache->image['id']);
	}

	//to get last image which is not optimized
	static function get_first_id(){

		$query_image = new \WP_Query(array(
			'order' => 'DESC',
			'orderby' => 'ID',
			'post_type' => 'attachment', 
			'post_mime_type' =>'image/jpeg, image/png, image/gif', 
			'post_status' => 'inherit',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => 'speedycache_optimisation',
					'compare' => 'NOT EXISTS'
				),
				array(
					'key' => '_wp_attachment_metadata',
					'compare' => 'EXISTS'
				)
			)
		));

		return count($query_image->posts) == 1 ? $query_image->posts[0]->ID : false;
	}

	static function statics_data(){
		$res = array(
			'total_image_number' => self::image_count(),
			'error' => self::error_count(),
			'optimized' => self::optimized_file_count(),
			'uncompressed' => self::uncompressed_count(),
			'reduction' => self::total_reduction(),
			'percent' => 0,
		);

		if($res['optimized'] > 0){
			$res['percent'] = ($res['optimized'] - $res['error']) * 100/$res['optimized'];
		}else{
			$res['percent'] = 0;
		}
		
		$res['percent'] = number_format($res['percent'], 2);
		$res['reduction'] = $res['reduction'];
		
		return $res;
	}

	static function revert_all(){
		global $speedycache;
		
		if(!current_user_can('manage_options')){
			wp_die('Must Be admin');
		}

		$images = new \WP_Query(array(
			'order' => 'DESC',
			'orderby' => 'ID',
			'post_type' => 'attachment',
			'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif'),
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'speedycache_optimisation',
					'compare' => 'EXISTS'
				)
			)
		));
		
		$failed = true;
		
		if(count($images->posts) <= 100){
			
			if($images->posts && count($images->posts) > 0){
				foreach($images->posts as $key => $post){
					$speedycache->image['id'] = $post->ID;
					$result = self::revert();
					
					if($result['success'] === true){
						$failed = false;
					}
				}
			}
			
			if(!empty($failed)){
				wp_send_json(array('success' => false, 'message' => __('Files can\'t be reverted', 'speedycache'))); 
			}
			
			wp_send_json(array('success' => true));
		}

		$schedule_posts = [];
		foreach($images->posts as $key => $post){
			
			if(100 === count($schedule_posts) || !empty($erase)){
				$schedule_posts = [];
			}

			$erase = false; // Flag to unset the $scheduled_posts
			
			$schedule_posts[] = $post->ID;

			// Check if we have reached 99 and make sure its not the last index
			if(count($schedule_posts) <= 99 && $key !== (count($images->posts) - 1)){
				continue;
			}
			
			// Skip if alredy on schedule list.
			if(wp_next_scheduled('speedycache_img_delete', array($schedule_posts))){
				$erase = true;
				continue;
			}
		
			$scheduled = self::get_optimization_schedule(array('speedycache_img_delete'));
			$time = time();
		
			if(!empty($scheduled) && isset(end($scheduled)['time'])){
				// getting the last index to get the last scheduled event
				$time = end($scheduled)['time'];
			}
			
			$final_schd_time = $time + 10;
		
			if(!wp_next_scheduled('speedycache_img_delete', array($schedule_posts))){
				wp_schedule_single_event($final_schd_time, 'speedycache_img_delete', array($schedule_posts));
				
				continue;
			}
		}

		wp_send_json(array('success' => true));
	}

	// Schedule deletion of image to reduce load on the server at a single time.
	static function scheduled_delete($img_id){
		global $speedycache;
		
		if(empty($img_id)){
			return;
		}

		if(is_scalar($img_id)){
			$speedycache->image['id'] = $img_id;
			self::revert();
			return;
		}
		
		foreach($img_id as $id){
			$speedycache->image['id'] = $id;
			self::revert();
		}
	}

	// Reverts a single image
	static function revert(){
		global $speedycache;
		
		if(empty($speedycache->image['id'])){
			return array('success' => false);
		}
		
		$optimization_data = get_post_meta($speedycache->image['id'], 'speedycache_optimisation', true);

		// optm = optimization
		$optm_json = base64_decode($optimization_data);
		$optm_json = json_decode($optm_json, true);
		
		if(empty($optm_json)){
			return array('success' => false);
		}

		if(!empty($optm_json) && is_countable($optm_json) && count($optm_json) == 1 && !empty($optm_json[0])){
			if(!empty($optm_json[0]['error_code']) && $optm_json[0]['error_code'] == 18){
				delete_post_meta($speedycache->image['id'], 'speedycache_optimisation');
				delete_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction');

				return array('success' => true);
			}
		}

		$result = false;

		$optm_json = array_reverse($optm_json);
		$error_numbers = 0;
		
		foreach($optm_json as $key => $image){

			if(@!is_writable($image['file'])){
				if(isset($speedycache->image['metadata']['file']) && preg_match('/'.preg_quote($speedycache->image['metadata']['file'], '/').'/', $image['url'])){
					if(file_exists($image['file'])){
						$result = array('success' => true, 'message' => $image['file'].' is not writable');
						break;
					}
					
					delete_post_meta($speedycache->image['id'], 'speedycache_optimisation');
					delete_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction');
				}
			}

			if(isset($image['destination_path']) && file_exists($image['destination_path'])){
				@unlink($image['file']);

				delete_post_meta($speedycache->image['id'], 'speedycache_optimisation');
				delete_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction');
				$result = array('success' => true);
				
				continue;
			}

			if(!empty($image['error_code'])){
				if(isset($speedycache->image['metadata']['file']) && preg_match('/'.preg_quote($speedycache->image['metadata']['file'], '/').'/', $image['url'])){
					delete_post_meta($speedycache->image['id'], 'speedycache_optimisation');
					delete_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction');
					$result = array('success' => true);
				}else{
					$error_numbers++;

					if($error_numbers == count($optm_json)){
						delete_post_meta($speedycache->image['id'], 'speedycache_optimisation');
						delete_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction');
						$result = array('success' => true);
					}
				}
				
				continue;
			}

			if(preg_match('/'.preg_quote($speedycache->image['metadata']['file'], '/').'/', $image['url'])){
				delete_post_meta($speedycache->image['id'], 'speedycache_optimisation');
				delete_post_meta($speedycache->image['id'], 'speedycache_optimisation_reduction');
				$result = array('success' => true);
			}

		}
		
		return $result;
	}

	static function revert_on_delete($id){
		global $speedycache;
		
		if(!wp_attachment_is_image($id)){
			return;
		}
		
		$speedycache->image['id'] = $id;
		
		self::revert();
	}

	static function get_error_text($id){
		
		//Error Codes
		$errors = array(
			2 => __('In backup folder parent folder not writable', 'speedycache'),
			3 => __('No need to optimize', 'speedycache'),
			4 => __('Source is not writable', 'speedycache'),
			5 => __('Destination is not writable', 'speedycache'),
			6 => __('ImageMagick library is not avaliable', 'speedycache'),
			7 => __('Error via api', 'speedycache'),
			8 => __('Source file does not exist', 'speedycache'),
			9 => __('Image size exceed 20mb limit while processing', 'speedycache'),
		   11 => __('Empty Name', 'speedycache'),
		   12 => __('Forbidden', 'speedycache'),
		   13 => __('CloudFlare to restrict access', 'speedycache'),
		   14 => __('No Extension', 'speedycache'),
		   15 => __('Image size is 0Kb', 'speedycache'),
		   16 => __('Corrupted Image', 'speedycache'),
		   17 => __('Empty metadata', 'speedycache'),
		   18 => __('No Image', 'speedycache'),
		   19 => __('destination_move_source_path is not saved', 'speedycache'),
		   20 => __('file size of destination_move_source_path is zero', 'speedycache'),
		   21 => __('Unacceptable file type', 'speedycache')
		);

		return isset($errors[$id]) ? $errors[$id] : 'Unkown error code';
	}

	// TODO:: Will use it when we will do non-webp compression
	static function backup_folder_exist(){
		global $speedycache;
		
		$backup_folder_path = $speedycache->image['upload_dir']['basedir'].'/speedycache-backup';

		if(is_dir($backup_folder_path)){
			return true;
		}

		if(@mkdir($backup_folder_path, 0755, true)){
			return true;
		}
		return false;

	}

	static function push_main_image($arr){
		if(!isset($arr[0]) || isset($arr[0]->error_code)){
			return $arr;
		}

		$main = clone $arr[0];
		$total_reduction = 0;

		foreach($arr as $std_key => $std_value){
			if(!isset($std_value->error_code)){
				if(isset($std_value->reduction) && $std_value->reduction){
					$total_reduction = $total_reduction + $std_value->reduction;
				}
			}
		}

		$main->reduction = $total_reduction;

		array_unshift($arr, $main);

		return $arr;
	}

	static function list_content($query_images_args = array()){
		global $speedycache;
		
		remove_filter('posts_fields', '\SpeedyCache\Image::count_posts_fields'); //was causing bug
		
		$query_image = new \WP_Query( $query_images_args );
		$return_output = '';
		
		if(empty($query_image->posts) || count($query_image->posts) <= 0){
			return self::get_empty_row();
		}
		
		$count = 0;
		foreach($query_image->posts as $key => $post){
			$count++;
			$value_json = get_post_meta($post->ID, 'speedycache_optimisation', true);
			$tmpvalue_json = base64_decode($value_json);
			
			$std = json_decode($tmpvalue_json);

			$revert = true;

			$std = self::push_main_image($std);
			
			foreach($std as $std_key => $std_value){
				
				$content = ($std_key === 0) ? self::get_row() : self::get_child_row();

				if(empty($content)){
					continue;
				}

				$std_value->destination_path = isset($std_value->destination_path) ? $std_value->destination_path : '';
				$std_value->reduction = isset($std_value->reduction) ? $std_value->reduction : 0;
				
				if($std_key === 0 && $revert){
					$revert_button = '';
				}else{
					$revert_button = 'display:none;';
				}

				if(isset($std_value->error_code) && $std_value->error_code == 8){
					$revert_button = 'display:none;';
				}

				if(file_exists($std_value->destination_path)){
					$backup_url = $std_value->url.'?v='.time();
					$backup_title = 'Original Image';
					$backup_error_style = '';
				}else{
					if(isset($std_value->error_code) && $std_value->error_code){
						$backup_url = get_edit_post_link($std_value->id);
						$backup_title = self::get_error_text($std_value->error_code);
						$backup_error_style = 'color: #FF0000;cursor:pointer;font-weight:bold;';
					}else{
						$backup_url = '#';
						$backup_title = '';
						$backup_error_style = '';
					}
				}
				
				$std_value->file = preg_replace('/.(jpg|jpeg|png|gif)$/','.webp', $std_value->file);
				
				if(file_exists($std_value->file)){
					$std_value->url = preg_replace('/.(jpg|jpeg|png|gif)$/','.webp', $std_value->url).'?v='.time();
				}else{
					$std_value->url = SPEEDYCACHE_PRO_URL.'/assets/images/no-image.png';
				}

				$short_code = array(
					'{{post_id}}',
					'{{attachment}}',
					'{{post_title}}',
					'{{url}}',
					'{{width}}',
					'{{height}}',
					'{{reduction}}',
					'{{date}}',
					'{{revert_button}}',
					'{{backup_url}}',
					'{{backup_title}}',
					'{{backup_error_style}}'
				);
				
				$datas = array(
					$std_value->id,
					$std_value->url,
					$post->post_title,
					$std_value->url,
					$std_value->width,
					$std_value->height,
					$std_value->reduction/1000,
					date('d-m-Y <br> H:i:s', $std_value->time),
					$revert_button,
					$backup_url,
					$backup_title,
					$backup_error_style
				);
			
				$return_output .= str_replace($short_code, $datas, $content);
			}
		}
		return $return_output;
	}

	static function allowed_mime($filename){
		global $speedycache;
		
		$mimetype = false;

		if(!file_exists($filename)){
			return false;
		}

		if(function_exists('finfo_open')){
		   $finfo = finfo_open(FILEINFO_MIME_TYPE);
		   $mimetype = finfo_file($finfo, $filename);
		   finfo_close($finfo);
		}else if(function_exists('getimagesize')){
		   $img = getimagesize($filename);
		   $mimetype = $img['mime'];
		}else{
			echo 'not found mime_content_type';
			exit;
		}
		
		if($speedycache->image['settings']['compression_method'] === 'cwebp' && self::gif2webp_exists()){
			if(preg_match('/(jpg|jpeg|jpe|png|gif)/i', $mimetype)){
				return true;
			}
			
			return false;
		}

		if(preg_match('/(jpg|jpeg|jpe|png)/i', $mimetype)){
			return true;
		}
		
		return false;
	}

	// Check if the image is available, the response code should be 200
	static function not_found($url){
		return false;
		
		$res = wp_remote_head($url, array('timeout' => 3 ));

		if(is_wp_error($res)){
			$url_header = @get_headers($url);

			if(preg_match('/200\s+OK/i', $url_header[0])){
				return false;
			}
			
			return true;
		}
		
		if($res['response']['code'] == 200){
			return false;
		}

		return true;
	}

	//Checks if the image is Unique
	static function unique_array($images){
		if(count($images) <= 1){
			return $images;
		}

		$arr = array();
		$images_tmp = array();

		foreach($images as $key => $value){
			if(!in_array($value['file'], $arr)){
				array_push($arr, $value['file']);
				array_push($images_tmp, $value);
			}
		}
		
		return $images_tmp;
	}
	//Template static functions Starts Here

	//Table row for the converted Image
	static function get_row(){
		return '<tr class="alternate author-self status-inherit" post-id="{{post_id}}">
			<td class="column-icon media-icon image-icon">
				<img width="39" height="60" src="{{attachment}}" class="attachment-80x60">
			</td>
			<td class="title column-title">
				<p style="margin-bottom: 0;"><a href="{{url}}" target="_blank">{{post_title}}</a></p>
				<p style="margin-bottom: 0;">
					<a style="{{backup_error_style}}" href="{{backup_url}}" target="_blank"><strong>{{backup_title}}</strong></a>
				</p>
			</td>
			<td class="author column-author speedycache-open-image-details" style="text-align: center;cursor: pointer;">{{reduction}}KB<span class="dashicons dashicons-arrow-down-alt2"></span></td>
			<td class="date column-date" style="text-align: center;">{{date}}</td>
			<td class="date column-date" style="text-align: center;">
			<div class="speedycache-revert" style="{{revert_button}}">
				<input type="hidden" value="{{post_id}}">
			</div>
			</td>
		</tr>';
	}

	//table row for different sizes of image.
	static function get_child_row(){
		
		return '<tr class="alternate author-self status-inherit" post-id="{{post_id}}" post-type="detail" style="display: none; padding-left: 20px;">
			<td class="column-icon media-icon" style="font-size:3.5em; color:#ccc;"><i class="fas fa-image"></i></td>
			<td class="title column-title">
				<p style="margin-bottom: 0;"><a href="{{url}}" target="_blank">{{post_title}}</a></p>
				<p style="margin-bottom: 0;">
					<a style="{{backup_error_style}}" href="{{backup_url}}" target="_blank"><strong>{{backup_title}}</strong></a>
				</p>
				<p style="margin-bottom: 0;">{{width}}x{{height}}</p>
			</td>
			<td class="author column-author" style="text-align: center;">{{reduction}}KB</td>
			<td class="date column-date" style="text-align: center;">{{date}}</td>
			<td class="date column-date" style="text-align: center;">
			</td>
		</tr>';
	}

	//No Image optimized
	static function get_empty_row(){
		return '<tr class="author-self status-inherit">
			<td colspan="4">No image has been optimized yet</td>
		</tr>';

	}

	//Paging for the converted Images Table
	static function paging(){ ?>
		<div class="tablenav bottom" style="padding:5px 16px 5px 19px">
			<div style="float:left;">
				<span class="deleteicon">
					<input type="text" style="height:28px;" placeholder="Search" id="speedycache-image-search-input" class="deletable" value="">
					<span class="cleared"></span>
				</span>
				<input type="submit" class="button action" value="Search" id="speedycache-image-search-button" name="filter_action">
			</div>
			<div style="float:left;padding-left:5px;">
				<select id="speedycache-image-list-filter" class="bulk-action-selector-top">
					<option value="" selected="selected">All</option>
					<option value='error_code'>Errors</option>
				</select>
			</div>
			<div style="float:left;padding-left:5px;">
				<select id="speedycache-image-per-page" class="bulk-action-selector-top">
					<option value="5" selected="selected">5</option>
					<option value="10">10</option>
					<option value="25">25</option>
				</select>
			</div>
			<div class="tablenav-pages">
				<span class="pagination-links">
					<a class="tablenav-pages-navspan button first-page disabled speedycache-image-list-first-page" aria-hidden="true" data-page-action="first-page">«</a>
					<a class="tablenav-pages-navspan button prev-page speedycache-image-list-prev-page" aria-hidden="true" data-page-action="prev-page">‹</a>
					<a class="paging-input"><label class="speedycache-current-page">1</label> / <span class="total-pages speedycache-total-pages">1</span></a>
					<a class="next-page button speedycache-image-list-next-page" data-page-action="next-page">›</a>
					<a class="last-page button speedycache-image-list-last-page" data-page-action="last-page">»</a>
				</span>
			</div>
			<br class="clear">
		</div>
	<?php
	}

	//Settings for Image Optimization
	static function settings(){
		global $speedycache, $speedycache_optm_method; 

		?>
		<div class="speedycache-block">
			<div class="speedycache-block-title"><h2><?php _e('Settings', 'speedycache'); ?></h2></div>
			<form class="speedycache-img-opt-settings">
				<div class="speedycache-option-wrap">
					<label for="speedycache_img_automatic_optm" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_img_automatic_optm" name="img_automatic_optm" <?php echo (isset($speedycache->image['settings']['automatic_optm']) && $speedycache->image['settings']['automatic_optm']) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name"><?php esc_html_e('Automatic optimization', 'speedycache'); ?></span>
						<span class="speedycache-option-desc"><?php esc_html_e('Whenever user visits the website and there are images that haven\'t been converted to webp then that image(s) gets converted.', 'speedycache'); ?></span>
					</div>
				</div>
				<div class="speedycache-option-wrap">
					<label for="speedycache_img_on_upload" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_img_on_upload" name="img_on_upload" <?php echo (isset($speedycache->image['settings']['on_upload']) && $speedycache->image['settings']['on_upload']) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name"><?php esc_html_e('Optimize on Image Upload', 'speedycache'); ?></span>
						<span class="speedycache-option-desc"><?php esc_html_e('Images will be added to a queue and will optimized after a certain interval to reduce load on the server.', 'speedycache'); ?></span>
					</div>
				</div>
				
				<div class="speedycache-option-wrap">
					<label for="speedycache_img_url_rewrite" class="speedycache-custom-checkbox">
						<input type="checkbox" id="speedycache_img_url_rewrite" name="img_url_rewrite" <?php echo (isset($speedycache->image['settings']['url_rewrite']) && $speedycache->image['settings']['url_rewrite']) ? ' checked="true"' : '';?>/>
						<div class="speedycache-input-slider"></div>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name"><?php esc_html_e('Rewrite Image URL\'s', 'speedycache'); ?></span>
						<span class="speedycache-option-desc"><?php esc_html_e('Rewrites the url of the image on the page to the webp version if webp version is avaliable.', 'speedycache'); ?></span>
					</div>
				</div>

				<div class="speedycache-option-wrap">
					<label for="speedycache_img_quality">
						<input type="number" id="speedycache_img_quality" name="img_compression_quality" value="<?php echo esc_attr($speedycache->image['settings']['compression_quality']);?>" min="50" step="5" max="90"/>
					</label>
					<div class="speedycache-option-info">
						<span class="speedycache-option-name"><?php esc_html_e('Compression Quality', 'speedycache'); ?></span>
						<span class="speedycache-option-desc"><?php esc_html_e('Higher the number higher will be the converted image quality in webp', 'speedycache'); ?></span>
					</div>
				</div>
				
				<div class="speedycache-block-title" style="flex-direction:column">
					<h4><?php esc_html_e('Conversion Methods', 'speedycache'); ?></h4>
					<p style="display:block; font-family: monospace; background-color: #f2f2f2; border-radius:6px; padding:10px;"><span style="color:var(--speedycache-red); font-weight:bold;"><i class="fas fa-exclamation"></i> Note:</span><?php esc_html_e('Only cwebp supports gif conversions to webp. But when you upload an image to WordPress whether it be a jpg, png, or gif format, WordPress creates different sizes of that image on upload so, in the process of resizing some resized version of gifs may lose their animation.', 'speedycache'); ?>
				</div>
				<?php foreach($speedycache_optm_method as $m_key => $method){ ?>
					<div class="speedycache-option-wrap <?php echo !$method['status'] ? ' speedycache-disabled-methods' : '' ?>">
						<label for="speedycache-img-<?php echo esc_attr($m_key);?>">
							<input type="radio" value="<?php echo esc_attr($m_key);?>" name="img_compression_method" id="speedycache-img-<?php echo esc_attr($m_key);?>" <?php echo (isset($speedycache->image['settings']['compression_method']) && $speedycache->image['settings']['compression_method'] == $m_key ? ' checked="true"' : '');?> <?php echo !$method['status'] ? ' disabled' : '' ?>/>
						</label>
						<div class="speedycache-option-info">
							<span class="speedycache-option-name"><?php echo esc_html($method['title']); ?></span>
							<span class="speedycache-option-desc"><?php echo esc_html($method['desc']); ?></span>
						</div>
						<?php 
						// TODO:: Need to fix this _e as it is a dynamic value we will need to use sprintf
						if(!$speedycache_optm_method[$m_key]['status']){ ?>
							<div class="speedycache-more-info" data-info="<?php echo esc_html($speedycache_optm_method[$m_key]['message']);?>" >
								<i class="fas fa-question-circle"></i>
							</div>
						<?php } ?>
							<div class="speedycache-img-method-actions">
						<?php 
						
						if($m_key == 'cwebp'){
							if(defined('SPEEDYCACHE_PRO') && !file_exists(wp_upload_dir()['basedir']. '/speedycache-binary/'.$speedycache->image['cwebp_binary'])){
								echo '<button style="margin-right:5px;" class="speedycache-btn speedycache-btn-primary speedycache-webp-download">Download Binary</button>';
							}
							
							if(defined('SPEEDYCACHE_PRO') && !self::gif2webp_exists()){
								echo '<button class="speedycache-btn speedycache-btn-primary speedycache-webp-download" data-type="gif">Download Binary for GIF</button>';
							}
						}
						?>
						</div>
					</div>	
				<?php } ?>
			</form>
		</div>
	<?php 
	}


	//Image Optimization Stats
	static function statics(){ 
		$res = self::statics_data();
		$ring_success = 100 - (int)$res['percent'];
		
		$scheduled = self::get_optimization_schedule(array('speedycache_auto_optm', 'speedycache_img_delete'));
		$scheduled_count = count($scheduled);
		
		//the css below is written in one line to make regex replace easy in js update.
	?>
		<style>
			@keyframes donut1 {
			0%{stroke-dasharray: 0, 100;}
			100%{stroke-dasharray: <?php echo esc_attr($res['percent'] .', ' . $ring_success);?>;}
		}
		</style>
		<div class="speedycache-block">
			<div class="speedycache-block-title"><h2><?php esc_html_e('Image Stats', 'speedycache'); ?></h2></div>
			
			<div class="speedycache-img-stats">
				<div class="speedycache-card speedycache-img-graph">
					<div class="speedycache-card-body">
						<div class="speedycache-donut-wrap">
							<svg width="100%" height="100%" viewBox="0 0 40 40" class="speedycache-donut">
								<circle class="donut-hole" cx="20" cy="20" r="15.91549430918954" fill="#fff"></circle>
								<circle class="speedycache-donut-ring" cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke-width="3.5"></circle>
								<circle class="speedycache-donut-segment speedycache-donut-segment speedycache-donut-segment-2" cx="20" cy="20" r="15.91549430918954" fill="transparent" stroke-width="3.5" stroke-dasharray="<?php echo esc_attr($res['percent']. ' '.$ring_success);?>" stroke-dashoffset="25"></circle>
								
								<g class="speedycache-donut-text speedycache-donut-text-1">
									<text y="50%" transform="translate(0, 2)">
										<tspan x="50%" text-anchor="middle" class="speedycache-donut-percent"><?php echo esc_html($res['percent']);?>%</tspan>   
									</text>
									<text y="60%" transform="translate(0, 2)">
										<tspan x="50%" text-anchor="middle" class="speedycache-donut-data">Success</tspan>   
									</text>
								</g>
							</svg>
						</div>
						<div class="speedycache-img-start-optm">
							<div class="speedycache-img-stat-info">
								<div class="speedycache-img-main-stat">
									<?php esc_html_e('Success', 'speedycache'); ?>
									<span class="speedycache-img-success-per"><?php echo esc_html($res['percent']);?>%</span>
								</div>
								<div class="speedycache-img-main-stat">
									<?php esc_html_e('Error', 'speedycache');?>
									<span class="speedycache-img-error-count" <?php echo $res['error'] > 0 ? 'style="color:#dc3545"' : '' ?>><?php echo esc_html($res['error']);?></span>
								</div>
								<div class="speedycache-img-main-stat">
									<?php esc_html_e('Scheduled', 'speedycache');?>
									<span class="speedycache-scheduled-count <?php echo $scheduled_count > 0 ? 'speedycache-scheduled-count-indicator' : '';?>" setting-id="speedycache-img-scheduled-modal"><?php echo esc_html($scheduled_count);?></span>
								</div>
							</div>
							<div class="speedycache-img-remain-optm">
								<span class="speedycache_img_optm_status" style="background-color:<?php echo $res['uncompressed'] > 0 ? '#EED202' : '#90ee90'?>"></span>
								<span>
								<?php 
									if($res['uncompressed'] > 0){
										echo esc_html($res['uncompressed']) . ' ';
										esc_html_e('Files needed to be optimized', 'speedycache');
									} else {
										esc_html_e('All images are optimized', 'speedycache');
									}	
								?></span>
									
							</div>
							<div class="speedycache-img-optimize-all" style="margin-left:auto;">
								<button class="speedycache-btn speedycache-btn-primary speedycache-img-optm-btn" setting-id="speedycache-modal-optimize-all"><?php esc_html_e('Optimize All', 'speedycache'); ?></button>
								<button class="speedycache-img-delete-all-conv speedycache-btn speedycache-btn-secondary"><?php esc_html_e('Delete all conversions', 'speedycache'); ?></button>
							</div>
						</div>
					</div>
				</div>
		
				<div class="speedycache-card speedycache-img-count">
					<div class="speedycache-card-body" style="height:200px;">
						<div class="speedycache-img-opt-stat">
							<div class="speedycache-img-optm-count"><?php echo esc_html($res['optimized'] . '/' . $res['total_image_number']); ?></div>
							<div class="speedycache-img-optm-count-text"><?php echo esc_html_e('Image(s) optimized', 'speedycache'); ?>
							<?php echo esc_html_e('with total reduction of', 'speedycache'); ?> <span class="speedycache-img-reduced-size"><?php echo esc_html($res['reduction'] > 10000 ? round($res['reduction']/1000, 2).'MB' : $res['reduction'].'KB');?></span></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<div modal-id="speedycache-img-scheduled-modal" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-header">
					<div><?php esc_html_e('Scheduled Tasks', 'speedycache'); ?></div>
					<div title="Close Modal" class="speedycache-close-modal">
						<span class="dashicons dashicons-no"></span>
					</div>
				</div>
				<div class="speedycache-modal-content speedycache-info-modal">
					<?php if($scheduled_count < 1){ ?>
						<span><?php esc_html_e('No Image Scheduled', 'speedycache'); ?></span>
					<?php } else { ?>
					<!--<p style="text-align:center;"><?php //_e('Time Interval of the Schedule is 1 minute', 'speedycache');?></p>-->
					<table style="margin:auto; width: 100%;">
						<thead>
							<tr>
								<th class="speedycache-table-hitem" scope="col"><?php esc_html_e('Image', 'speedycache'); ?></th>
								<th class="speedycache-table-hitem" scope="col"><?php esc_html_e('Time', 'speedycache'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($scheduled as $key => $schedule){

								$time = $schedule['time'] - time();

								$time_str = 'in '.$time. ' seconds';
								
								if($time > 59){
									$time_str = 'in '. round($time/60, 1) . ' minute(s)';
								} else if($time <= 0){
									$time_str = 'now';
								}
								?>
							<tr>
								<td class="speedycache-table-item"><?php echo esc_html('Batch ' . $key + 1); ?></td>
								<td class="speedycache-table-item" style="text-align:right;"><?php echo esc_html($time_str);?> <i class="fas fa-stopwatch"></i></td>
							</tr>
							<?php } ?>
							
						</tbody>
					</table>
					<?php } ?>
				</div>
			</div>
		</div>
		
		<div modal-id="speedycache-modal-all-img-revert" class="speedycache-modal">
			<div class="speedycache-modal-wrap">
				<div class="speedycache-modal-content">
					<i class="fas fa-info-circle"></i>
					<h1><?php esc_html_e('Revert All Images!', 'speedycache'); ?></h1>
					<p><?php esc_html_e('Once deleted the changes won\'t be reversible.', 'speedycache'); ?></p>
					<div class="speedycache-modal-db-actions">
						<button class="speedycache-btn speedycache-db-confirm-yes"><?php esc_html_e('Yes', 'speedycache'); ?></button>
						<button class="speedycache-btn speedycache-db-confirm-no"><?php esc_html_e('No', 'speedycache'); ?></button>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	//Templates Ends here

	//Need to create logs for Automatic Optimization
	static function log(){
		
	}

	static function compression_method_checks(){
		global $speedycache, $speedycache_optm_method;
		
		$gd = self::gd_check();
		$speedycache_optm_method['gd'] = array_merge($speedycache_optm_method['gd'], $gd);
		
		if(!$speedycache_optm_method['gd']['status']){
			$speedycache->image['disabled_method']++;
			self::change_method_if_not_available('gd');
		}
		
		$imagick = self::imagick_check();
		$speedycache_optm_method['imagick'] = array_merge($speedycache_optm_method['imagick'], $imagick);
		
		if(!$speedycache_optm_method['imagick']['status']){
			$speedycache->image['disabled_method']++;
			self::change_method_if_not_available('imagick');
		}
		
		$cwebp = self::cwebp_check();
		$speedycache_optm_method['cwebp'] = array_merge($speedycache_optm_method['cwebp'], $cwebp);
		
		if(!$speedycache_optm_method['cwebp']['status']){
			$speedycache->image['disabled_method']++;
			self::change_method_if_not_available('cwebp');
		}
	}

	// GD static function Starts here
	static function gd_check(){
		if(!extension_loaded('gd')){
			return array('status' => false, 'message' => 'You dont have GD PHP extension');
		}

		if(isset(gd_info()['WebP Support']) && !gd_info()['WebP Support']){
			return array('status' => false, 'message' => 'GD extension on your server dosen\'t support WEBP');
		}
		
		if(isset(gd_info()['WebP Support']) && gd_info()['WebP Support']){
			return array('status' => true, 'message' => 'Operational');
		}
	}

	static function gd_webp($file){
		global $speedycache, $speedycache_optm_method;

		if(isset($speedycache_optm_method['gd']['status']) && !$speedycache_optm_method['gd']['status']){
			return array('success' => false, 'error_message' => $speedycache_optm_method['gd']['message']);
		}
		
		$image_size = getimagesize($file);
		$width = $image_size[0];
		$height = $image_size[1];
		
		switch($image_size['mime']){
			case 'image/jpg':
			case 'image/jpeg':
				$source = imagecreatefromjpeg($file);
				break;
			
			case 'image/png':
				$source = imagecreatefrompng($file);
				break;
				
			case 'image/webp': 
				return array('success' => false, 'error_message' => 'File is alredy a WEBP file');
				
			default:
				return array('success' => false, 'error_message' => 'File has unsupported mime'.$image_size['mime']);
		}
		
		if(empty($source)){
			return false;
		}
		
		$dst = imagecreatetruecolor($width, $height);
		
		if(imagealphablending($dst, false) !== false){
			//change the RGB values if you need, but leave alpha at 127
			$transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);

			if($transparent !== false){
				//simpler than flood fill
				if(imagefilledrectangle($dst, 0, 0, $width, $height, $transparent) !== false){
					//restore default blending
					if(imagealphablending($dst, true) !== false){
						if(imagecopy($dst, $source, 0, 0, 0, 0, $width, $height) !== false){
							$success = true;
						}
					};
				}
			}
		}
		
		if(!$success){
			return array('success' => false, 'error_message' => 'Couldn\'t convert the given image.');
		}
		
		$webp_path = preg_replace('/.(jpe?g|png)$/', '.webp', $file);
		
		imagedestroy($source);
		imagewebp($dst, $webp_path, $speedycache->image['settings']['compression_quality']);
		imagedestroy($dst);
		
		if(!file_exists($webp_path)){
			return array('url' => $webp_path, 'success' => true, 'error_message' => 19);
		}

		return array(
			'url' => $webp_path,
			'success' => true,
			'error_message' => ''
		);
	}
	//GD Ends Here

	//Imagick static functions
	static function imagick_check(){
		
		if(!extension_loaded('imagick')){
			return array('status' => false, 'message' => 'Imagick extension isn\'t installed');
		}
		
		$image = new \Imagick();
		
		if(!in_array('WEBP', $image->queryFormats('WEBP'))){
			$image->clear();
			$image->destroy();
		
			return array('status' => false, 'message' => 'Imagick installed on your system is without WEBP support');
		}
		
		return array('status' => true, 'message' => 'Operational');
	}

	static function change_method_if_not_available($m_name){
		global $speedycache, $speedycache_optm_method;
		
		if($speedycache->image['settings']['compression_method'] != $m_name){
			return;
		}
		
		$valid_method = '';
		
		foreach($speedycache_optm_method as $key => $method){
			if($key == $m_name){
				continue;
			}
			
			if(!empty($method['status'])){
				$speedycache->image['settings']['compression_method'] = $key;
				return;
			}
		}

		$speedycache->image['settings']['compression_method'] = $valid_method;
	}

	//Conversion of image to webp
	static function imagick_webp($file){
		global $speedycache, $speedycache_optm_method;
		
		if(isset($speedycache_optm_method['imagick']['status']) && !$speedycache_optm_method['imagick']['status']){
			return array('success' => false, 'error_message' => $speedycache_optm_method['imagick']['message']);
		}
		
		$dest = preg_replace('/.(png|jpe?g)$/', '.webp', $file);
		
		$image = new \Imagick();
		$image->readImage($file);
		$image->setImageCompressionQuality($speedycache->image['settings']['compression_quality']);
		$image->setImageFormat('WEBP');
		
		if($image->writeImage($dest)){
			$image->clear();
			$image->destroy();
			
			return array('success' => true, 'error_message' => '');
		}
		
		$image->clear();
		$image->destroy();
		
		return array('success' => false, 'error_message' => 'File didn\'t got saved');
	}

	//cwebp static functions
	static function cwebp_check(){
		global $speedycache;
		
		if(!defined('SPEEDYCACHE_PRO')){
			return array('status' => false, 'message' => 'cwebp is a SPEEDYCACHE PRO feature.');
		}
		
		if(!function_exists('exec')){
			return array('status' => false, 'message' => 'You dont have access to exec static function hence you cant use cWebP for Image optimizations');
		}
		
		if(isset($speedycache->image['cwebp_binary'])){
			
			if(!file_exists(wp_upload_dir()['basedir'].'/speedycache-binary/'.$speedycache->image['cwebp_binary'])){
				return array('status' => false, 'message' => 'You don\'t have the cwebp Binary on your server.', 'downloaded' => false);
			} 
		}
		
		return array('status' => true, 'message' => 'Operational');
	}

	static function rewrite_url_to_webp($content){
		if(strpos(speedycache_optserver('HTTP_ACCEPT'), 'image/webp') < 0){
			return $content;
		}
		
		if( ! preg_match_all( '/<img\s[^>]+>/', $content, $matches, PREG_SET_ORDER ) ){
			return $content;
		}
		
		foreach($matches as $match){
			///(?i)(https?:\/\/|www.|\w+\.(png|jpe?g)$)[^\s]+/
			preg_match_all('/https?:\/(\/[^\/]+)+\.(?:jpe?g|png|gif)/', $match[0], $urls, PREG_SET_ORDER); 
			
			foreach($urls as $url){
				$file_url = preg_replace('/.(jpe?g|png|gif)$/', '.webp', $url[0]);
				
				$file_path = explode(SPEEDYCACHE_WP_CONTENT_DIR.'/uploads', $file_url);

				if(!isset($file_path[1]) || !file_exists(wp_upload_dir()['basedir'].$file_path[1])){
					continue;
				}
				
				$content = str_replace($url[0], $file_url, $content);
			}
		
		}
		
		return $content;
	}

	//Adding wp event for automatic optm on image upload
	static function convert_on_upload($id){
		global $speedycache;

		if(!$speedycache->image['settings']['on_upload']){
			return;
		}
		
		if(!wp_attachment_is_image($id)){
			return;
		}
		
		$scheduled = self::get_optimization_schedule(array('speedycache_auto_optm'));
		$time = time();
		
		if(!empty($scheduled) && isset(end($scheduled)['time'])){
			$time = end($scheduled)['time'];
		}
		
		//Convert after 5 minutes of upload
		if(!wp_next_scheduled('speedycache_auto_optm', array($id))){
			wp_schedule_single_event($time + 300, 'speedycache_auto_optm', array($id));
			return;
		}
	}

	//Optimizing image on Upload
	static function auto_optimize($img_id){
		self::optimize_single($img_id);
	}

	//Whenever user visits a page and there are images that dont have a webp versions then those images are set to be converted.
	static function optimize_on_fly($content){
		global $wpdb, $speedycache;

		//Sitepad dosen't have this static function.
		if(function_exists('wp_filter_content_tags')){
			$content = wp_filter_content_tags($content);
		}

		if(!preg_match_all('/<img\s[^>]+>/', $content, $matches, PREG_SET_ORDER)){
			return $content;
		}
		
		foreach($matches as $m_key => $match){
			if(!preg_match('/wp-image-([\d]+)/i', $match[0], $post_id)){
				//This one will work on the new versions of pagelayer.
				if(!preg_match('/pl-image-([\d]+)/i', $match[0], $post_id)){
					/*
					* This is for compatibility with other editors.
					* what we do is find the url from 'src' attribute and then get the file name by 
					* exploding using "/" so the last index of array will be the file name
					* then we will look if that file name is present in any meta_value to get the Image id.
					*/
					
					//<img(?:[^>]|[^\/>]).*src[\s*]?=[\s*]?"(.+?)" just in case the one used causes bugs
					if(!preg_match('/<img.*?src="(.*?)"/', $match[0], $url)){
						continue;
					}
					
					$file = explode('/', esc_url($url[1]));
					$file = end($file);
					
					$query = "SELECT `post_id` FROM `".$wpdb->prefix."postmeta` WHERE `meta_value` LIKE '%$file%'";
					
					$result = $wpdb->get_row($query);

					if(empty($result) || !$result->post_id){
						continue;
					}
					
					$post_id = [$file, $result->post_id];
				}
			}
		
			if(empty($post_id)){
				continue;
			}

			$attachment_id = (int)$post_id[1];
			
			if(!$attachment_id){	
				continue;
			}
			
			//If the image is already scheduled for optimization then just skip it.
			if(wp_next_scheduled('speedycache_auto_optm', array($attachment_id))){
				continue;
			}
			
			if(!get_post($attachment_id)){
				continue;
			}
			
			$query = "SELECT count(`meta_key`) as optimized FROM `".$wpdb->prefix."postmeta` WHERE `meta_key`= 'speedycache_optimisation' AND `post_id`=$attachment_id";
			$result = $wpdb->get_row($query);
			
			if($result->optimized){
				continue;
			}
			
			/*
			* https?:\/(\/[^\/]+)+\.(?:jpe?g|png|gif) this regex dosent matches if it finds "//" anywhere after
			* the slashes of http://. Which was causing a issue in sitepad as after site-data/uploads// 2 slashes were added.
			* so the regex being used here dosent checks for "//" double slashes.
			*/
			// if(!preg_match('/https?:\/\/.*\.(?:jpe?g|png|gif)$/', $match[0], $url)){
				// continue;
			// }
			
			// $path = explode(SPEEDYCACHE_WP_CONTENT_DIR.'/uploads', $url[0]);
			// $path = wp_upload_dir()['basedir'] . $path[1];
			
			// if(!file_exists($path)){
				// continue;
			// }
			
			$scheduled = self::get_optimization_schedule(array('speedycache_auto_optm'));
			$time = time();
		
			if(!empty($scheduled) && isset(end($scheduled)['time'])){
				// getting the last index to get the last scheduled event
				$time = end($scheduled)['time'];
			}
			
			$final_schd_time = $time + 60;
		
			if(!wp_next_scheduled('speedycache_auto_optm', array($attachment_id))){
				wp_schedule_single_event($final_schd_time, 'speedycache_auto_optm', array($attachment_id));
				
				continue;
			}
		}

		return $content;
	}

	// Returns an array of cron event "speedycache_auto_optm"
	static function get_optimization_schedule($event_name){
		$cron = _get_cron_array();
		
		if(empty($cron)){
			return false;
		}
		
		$scheduled = array();
		
		foreach($cron as $key => $crn){
			foreach($crn as $e_key => $event){
				if(!in_array($e_key, $event_name)){
					continue;
				}

				foreach($event as $evt){
					$args = $evt['args'][0];
				}
				
				array_push($scheduled, array('name' => get_the_title($args), 'time' => $key));
			}
		}
		
		return $scheduled;
	}

	static function gif2webp_exists(){
		global $speedycache;
		
		if(file_exists(wp_upload_dir()['basedir']. '/speedycache-binary/'.$speedycache->image['cwebp_gif'])){
			return true;
		}
		
		return false;
	}
	
	static function download_cwebp(){
		global $speedycache;
		
		speedycache_verify_nonce(speedycache_optget('security'), 'speedycache_nonce');
		
		$binary_name = $speedycache->image['cwebp_binary'];
		
		$type = speedycache_optget('type');
		
		switch($type){
			case 'gif':
				$binary_name = $speedycache->image['cwebp_gif'];
				break;
				
			case 'cwebp':
				$binary_name = $speedycache->image['cwebp_binary'];
				break;
				
			default:
				return;
		}
		
		$binary_path = wp_upload_dir()['basedir'].'/speedycache-binary';
		
		if(!file_exists($binary_path)){
			@mkdir($binary_path);
		}
		
		//If The binary alredy exists on the server
		if(file_exists($binary_path.'/'.$binary_name)){
			wp_send_json(array('success' => false, 'error_message' => 'Binary Exists already.'));
		}
		
		$resp = wp_remote_get(SPEEDYCACHE_API.'/files/cwebp/'.$binary_name);
		$json = wp_remote_get(SPEEDYCACHE_API.'/files/hash.json');
		
		$hash = json_decode($json['body'], true);
		$file = $resp['body'];
		
		if(!$hash){
			wp_send_json(array('success' => false, 'error_message' => 'Unable to verify the downloaded binary.'));
		}
		
		if(!$file){
			wp_send_json(array('success' => false, 'error_message' => 'Could not download the file please try again later.'));
		}
		
		$binary_file = $binary_path.'/'.$binary_name;
		
		file_put_contents($binary_file, $file);
		
		if(!file_exists($binary_file)){
			wp_send_json(array('success' => false, 'error_message' => 'There was issue downloading the binary.'));
		}
		
		if(hash_file('md5', $binary_file) != $hash[$binary_name]){
			@unlink($binary_path.'/'.$binary_name);
			wp_send_json(array('success' => false, 'error_message' => 'Hash of the file downloaded didn\'t match'));
		}
			
		@chmod($binary_file, 0755);
		
		$output = null;
		$res = null;
		
		if(!function_exists('exec')){
			wp_send_json(array('success' => false, 'error_message' => 'Your server dosen\'t supports exec'));
		}
		
		$exec_name = $type === 'gif' ? 'gf2webp' : 'cwebp';
		
		/*
		* To check if the binary is supported by the server.
		*/
		exec('"'.$binary_file.'"  '.$exec_name.' -version 2>&1', $output, $res);
		
		if($res != 0){
			wp_send_json(array('success' => false, 'error_messsage' => 'The binary for cwebp isn\'t supported by your server'));
		}
		
		if($type = 'gif'){
			$speedycache->image['cwebp_gif'] = $binary_name;
		} else {
			$speedycache->image['cwebp_binary'] = $binary_name;
		}
		
		wp_send_json(array('success' => true));
	}

	static function cwebp_convert($file){
		global $speedycache, $speedycache_optm_method;
		
		if(isset($speedycache_optm_method['cwebp']['status']) && !$speedycache_optm_method['cwebp']['status']){
			return array('success' => false, 'error_message' => $speedycache_optm_method['cwebp']['message']);
		}
			
		$mime = mime_content_type($file);
		
		if($mime == 'image/gif'){
			$binary_path = wp_upload_dir()['basedir']. '/speedycache-binary/'.$speedycache->image['cwebp_gif'];
			$binary_name = 'gif2webp';
		} else {
			$binary_path = wp_upload_dir()['basedir']. '/speedycache-binary/'.$speedycache->image['cwebp_binary'];
			$binary_name = 'cwebp';
		}
		
		$output_file = preg_replace('/.(jpe?g|png|gif)$/', '.webp', $file);
		

		$res = null;
		$output = null;
		
		if(!file_exists($binary_path)){
			return array('success' => false, 'error_message' => 'The binary to use cwebp not found');
		}
		
		/*
		*	-- Use "" around the paths in exec, as without "" can cause a bug on some servers installs. 
		*	-- The Structure of the command below is :- 
		*	"path_of_binary" cwebp -q [quality_in_int] "original_file_path" -o "output_file.webp"
		*	-- 2>&1 is used to redirect stderr to same place as stdout
		*	https://www.brianstorti.com/understanding-shell-script-idiom-redirect/
		*/
		exec('"'.$binary_path.'" '.$binary_name.' -q '.$speedycache->image['settings']['compression_quality'].' "'.$file. '" -o "'. $output_file. '" 2>&1', $output, $res);
		
		if($res != 0){
			return array('success' => false, 'error_message' => 'Couldn\'t convert the given image using cwebp.', 'error_code' => 22);
		}
		
		return array('success' => true, 'error_message' => '', 'dest' => $output_file);
	}
	
	static function list_image_html(){
	?>
	<div id="speedycache-image-list">
		<?php \SpeedyCache\Image::paging(); ?>
		<div style="width:100%; overflow-x:auto;">
			<table class="wp-list-table widefat fixed media" style="width: 95%; margin-left: 20px;">
				<thead>
					<tr style="height: 35px;">
						<th scope="col" id="icon" class="manage-column column-icon" style=""></th>
						<th scope="col" id="title" class="manage-column column-title sortable desc" style="width: 323px;">
							<span style="padding-left: 8px;"><?php esc_html_e('File Name', 'speedycache'); ?></span>
						</th>
						<th scope="col" id="author" class="manage-column column-author sortable desc" style="width: 120px;text-align: center;">
							<span><?php esc_html_e('Reduction', 'speedycache'); ?></span>
						</th>
						<th scope="col" id="date" class="manage-column column-date sortable asc" style="width: 91px;text-align: center;">
							<span><?php esc_html_e('Date', 'speedycache'); ?></span>
						</th>
						<th scope="col" id="date" class="manage-column column-date sortable asc" style="width: 60px;text-align: center;">
							<span><?php esc_html_e('Revert', 'speedycache'); ?></span>
						</th>	
					</tr>
				</thead>
				<tbody id="the-list">
					<?php 
						$query_images_args = array();

						$query_images_args['order'] = 'DESC';
						$query_images_args['orderby'] = 'ID';
						$query_images_args['post_type'] = 'attachment';
						$query_images_args['post_mime_type'] = array('image/jpeg', 'image/png', 'image/gif');
						$query_images_args['post_status'] = 'inherit';
						$query_images_args['posts_per_page'] = 5;
						$query_images_args['meta_query'] = array(
							array(
								'key' => 'speedycache_optimisation',
								'compare' => 'EXISTS'
							)
						);

						echo \SpeedyCache\Image::list_content($query_images_args);
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php 
	}

}

