<?php

/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if( !defined('SPEEDYCACHE_PRO_VERSION') ){
	die('HACKING ATTEMPT!');
}

class Logs{

	static function log($type){
		global $speedycache;
		
		$speedycache->logs = array();
		$speedycache->logs['type'] = $type;
		$speedycache->logs['name'] = '';
		$speedycache->logs['limit'] = 0;
		$speedycache->logs['logs'] = array();
		
		if($speedycache->logs['type'] == 'delete'){
			$speedycache->logs['name'] = 'speedycache_delete_cache_logs';
			$speedycache->logs['limit'] = 25;
		}
		
		self::set_logs();
	}

	static function update_db(){
		global $speedycache;
		
		if(get_option($speedycache->logs['name'])){
			update_option($speedycache->logs['name'], $speedycache->logs['logs']);
		}else{
			update_option($speedycache->logs['name'], $speedycache->logs['logs'], null, 'no');
		}
	}

	static function set_logs(){
		global $speedycache;
		
		if($log = get_option($speedycache->logs['name'])){
			$speedycache->logs['logs'] = $log;
		}

	}

	// To detect which static function called delete_cache()
	static function decode_via($data){

		$return_res = '';
		
		switch($data['function']){
			case 'speedycache_delete_cache':
			case 'speedycache_delete_cache (speedycache-pro)':
				$return_res = '- Deleted From Manage Cache Tab';
				break;
			
			case 'speedycache_set_schedule':
				$return_res = '- Cache Timeout';
				break;
				
			case 'speedycache_options_page_request':
				$return_res = '- Delete Cache Button';
				break;
				
			case 'speedycache_delete_cache_toolbar':
				$return_res = '- Delete Cache Toolbar';
				break;
			
			case 'speedycache_column_clear_cache_column':
				$return_res = '- Delete Cache through Column action';
				break;
			
			case 'speedycache_delete_css_and_js_cache_toolbar':
				$return_res = '- Delete Cache and Minified CSS/JS through Toolbar';
				break;
			
			case 'speedycache_delete_css_and_js_cache':
				$return_res = '- Delete Cache and Minified CSS/JS Button';
				break;
				
			case 'varnish':
				$return_res = '- Varnish Cache got Purged Successfully';
				break;
			
			case 'on_status_transitions':
				$type = $data['args'][2]->post_type;
			
				if($data['args'][0] == 'publish' && $data['args'][1] == 'publish'){
					$return_res = '<span>- The '.$type.' has been updated</span><br><span>- #ID:'.$data['args'][2]->ID.'</span><br><span>- One cached file has been removed</span>';
				}else if($data['args'][0] == 'publish' && $data['args'][1] != 'publish'){
					$return_res = '<span>- New '.$type.' has been published</span><br><span>- '.$type.' ID:'.$data['args'][2]->ID.'</span>';
				}else {
					$return_res = '<span>- The '.$type.' status has been changed.</span><br><span>- '.$data['args'][1].' > '.$data['args'][0].'</span><span> #ID:'.$data['args'][2]->ID.'</span>';
				}
				
				break;
			
			case 'wp_set_comment_status':				
				if(isset($data['args'][0]->comment_ID)){
					$return_res = '<span>- Comment has been marked as </span>'.'<span>'.$data['args'][1].'</span><br><span>- Comment ID: '.$data['args'][0]->comment_ID.'</span><br><span>- One cached file has been removed</span>';
				}else{
					$return_res = '<span>- Comment has been marked as </span>'.'<span>'.$data['args'][1].'</span><br><span>- Comment ID: '.$data['args'][0].'</span><br><span>- One cached file has been removed</span>';
				}
				
				break;
				
			case 'clear_cache_after_woocommerce_checkout_order_processed':
				if(isset($data['args']) && is_array($data['args'])){
					$return_res = '<span>- New item has been ordered</span><br><span>- Product ID: '.implode(',', $data['args']).'</span>';
				}
				
				break;
			
			case 'speedycache_clear_all_cache':
				if(isset($data['file']) && $data['file']){
					$return_res = '<span>- '.$data['function'].'</span><br><span>'.$data['file'].'</span>';
				}
				break;
			
			case 'speedycache_single_delete_cache':
				if(!empty($data['args']) && !empty($data['args'][1])){
					$return_res = '- Cache Purged of Post ID -> ' . esc_html($data['args'][1]);
				} else {
					$return_res = '- Cache Cleared of a single Post';
				}
			
				break;
			
			case 'speedycache_set_schedule':
				if(!empty($data['args']) && !empty($data['args'][0])){
					$return_res = '- Cache Lifespan Ended' . !empty($data['args'][0]['prefix']) ? esc_html($data['args'][0]['prefix']): '';
				}

				break;
		}
		
		if(!empty($return_res)){
			return $return_res;
		}

		return $data['function'];
	}

	static function get_via(){
		$arr = array();
		$via = debug_backtrace();

		if(isset($via[8]) && ($via[8]['function'] == 'wp_set_comment_status') && ($via[2]['function'] == 'home_page_cache') && ($via[3]['function'] == 'speedycache_single_delete_cache')){
			return false;
		}
		
		if($via[3]['function'] == 'speedycache_delete_home_page_cache'){
			return false;
		}

		if($via[4]['function'] == 'clear_cache_after_woocommerce_checkout_order_processed'){
			$arr['args'] = array();
			$arr['function'] = $via[4]['function'];

			$order = wc_get_order($via[4]['args'][0]);

			if($order){
				foreach($order->get_items() as $item_key => $item_values ){
					array_push($arr['args'], $item_values->get_product_id());
				}
			}
		}elseif($via[4]['function'] === 'speedycache_set_schedule'){
			$arr['function'] = $via[4]['function'];
			$arr['args'] = $via[4]['args'];
		}elseif($via[2]['function'] == 'varnish'){
			$arr['function'] = $via[2]['function'];
		}else if($via[3]['function'] == 'on_status_transitions' || $via[3]['function'] == 'speedycache_single_delete_cache'){
			$arr['args'] = $via[3]['args'];
			$arr['function'] = $via[3]['function'];
		}else if($via[4]['function'] == 'speedycache_delete_css_and_js_cache_toolbar' || $via[4]['function'] == 'speedycache_delete_cache_toolbar'){
			$arr['function'] = $via[4]['function'];
		}else if(isset($via[7]) && ($via[7]['function'] == 'wp_set_comment_status')){
			$arr['args'] = $via[7]['args'];
			$arr['function'] = $via[7]['function'];
		}else if(isset($via[6]) && ($via[3]['function'] == 'apply_filters' && $via[6]['function'] == 'speedycache_clear_all_cache')){
			$arr['file'] = $via[6]['file'];
			$arr['function'] = $via[6]['function'];
		}else{
			$arr['function'] = $via[3]['function'];

			if(isset($via[3]['file']) && $via[3]['file']){
				if(preg_match("/\/plugins\/([^\/]+)\//", $via[3]['file'], $plugin_name)){
					$arr['function'] = $arr['function'].' ('.$plugin_name[1].')';
				}
			}
		}

		return $arr;
	}

	static function action($from = ''){
		global $speedycache;
		
		if($speedycache->logs['type'] == 'delete'){
			$log = [];
			$log['date'] = date('d-m-Y @ H:i:s', current_time('timestamp'));
			
			if($from && $from['prefix'] != 'all'){
				$log['via'] = [];
				$log['via']['function'] = '- Cache Timeout / '.$from['prefix'].' '.$from['content'];
			}else{
				$log['via'] = self::get_via();
			}
		}
		
		if($log && $log['via'] !== false){
			
			if(!in_array($log, $speedycache->logs['logs'])){
				array_unshift($speedycache->logs['logs'], $log);

				if($speedycache->logs['limit'] < count($speedycache->logs['logs'])){
					array_pop($speedycache->logs['logs']);
				}

				self::update_db();
			}
		}
	}

	static function print_logs(){
		global $speedycache;

		?>
		<div id="speedycache-delete-logs">
			<div class="speedycache-block">
				<div class="speedycache-block-title">
					<h2>Delete Cache Logs</h2>
				</div>

				<table class="speedycache-log-table">
					<thead>
						<tr>
							<th scope="col">Date</th>
							<th scope="col">Via</th>
						</tr>
					</thead>
					<tbody>
						<?php if($speedycache->logs['logs'] && count($speedycache->logs['logs']) > 0){ ?>
							<?php foreach($speedycache->logs['logs'] as $key => $log){ ?>
								<tr>
									<td scope="row"><?php echo isset($log['date']) ? esc_html($log['date']) : '';?></td>
									<td style="border-right:1px solid #DEDBD1;"><?php echo isset($log['via']) ? esc_html(self::decode_via($log['via'])) : ''; ?></td>
								</tr>
							<?php } ?>
						<?php }else{ ?>
								<tr>
									<td style="border-left:1px solid #DEDBD1;" scope="row"><label>No Log</label></td>
									<td style="border-right:1px solid #DEDBD1;"></td>
								</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

}

