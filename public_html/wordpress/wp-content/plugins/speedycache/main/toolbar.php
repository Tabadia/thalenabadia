<?php
/*
* SPEEDYCACHE
* https://speedycache.com/
* (c) SpeedyCache Team
*/

namespace SpeedyCache;

if( !defined('SPEEDYCACHE_VERSION') ){
	die('HACKING ATTEMPT!');
}

class Toolbar{

	static function add(){
		if(is_admin()){
			add_action('wp_before_admin_bar_render', '\SpeedyCache\Toolbar::tweak_on_admin_panel');
			add_action('admin_enqueue_scripts', '\SpeedyCache\Toolbar::js');
			add_action('admin_enqueue_scripts', '\SpeedyCache\Toolbar::css');
			return;
		}
		
		if(is_admin_bar_showing()){
			add_action('wp_before_admin_bar_render', '\SpeedyCache\Toolbar::tweak_on_frontpage');
			add_action('wp_enqueue_scripts', '\SpeedyCache\Toolbar::js');
			add_action('wp_enqueue_scripts', '\SpeedyCache\Toolbar::css');
			add_action('wp_footer',  '\SpeedyCache\Toolbar::inline_script');
		}
	}

	static function js(){
		wp_enqueue_script('speedycache-toolbar', SPEEDYCACHE_URL . '/assets/js/toolbar.js', array('jquery'), time(), true);
		
		wp_localize_script('speedycache-toolbar', 'speedycache_toolbar_ajax', array(
			'url' =>  admin_url().'admin-ajax.php',
			'nonce' => wp_create_nonce('speedycache_nonce'),
		));

	}

	//NOTE:: Remove this function toolbar css now in speedycache-admin css
	static function css(){
		wp_enqueue_style('speedycache-toolbar', SPEEDYCACHE_URL . '/assets/css/toolbar.css', array(), time(), 'all');
	}

	static function inline_script(){
	?>
		<script type="text/javascript">
			jQuery('body').append('<div class="speedycache-loader"><div class="speedycache-loader-circle"></div></div>');
		</script>
	<?php
	}

	static function tweak_on_frontpage(){
		global $wp_admin_bar, $speedycache;

		$wp_admin_bar->add_node(array(
			'id'    => 'speedycache-toolbar-parent',
			'title' => 'SpeedyCache'
		));

		$wp_admin_bar->add_menu(array(
			'id'    => 'speedycache-toolbar-parent-clear-cache-of-this-page',
			'title' => 'Clear Cache of This Page',
			'parent' => 'speedycache-toolbar-parent',
			'meta' => array('class' => 'speedycache-toolbar-child')
		));

		$wp_admin_bar->add_menu(array(
			'id'    => 'speedycache-toolbar-parent-delete-cache',
			'title' => 'Delete Cache',
			'parent' => 'speedycache-toolbar-parent',
			'meta' => array('class' => 'speedycache-toolbar-child')
		));

		$wp_admin_bar->add_menu(array(
			'id'    => 'speedycache-toolbar-parent-delete-cache-and-minified',
			'title' => 'Delete Cache and Minified CSS/JS',
			'parent' => 'speedycache-toolbar-parent',
			'meta' => array('class' => 'speedycache-toolbar-child')
		));

		if($speedycache->settings['is_multi']){
			$wp_admin_bar->add_menu(array(
				'id'    => 'speedycache-toolbar-parent-clear-cache-of-allsites',
				'title' => __('Clear Cache of All Sites', 'speedycache'),
				'parent' => 'speedycache-toolbar-parent',
				'meta' => array('class' => 'speedycache-toolbar-child')
			));
		}
	}

	static function tweak_on_admin_panel(){
		global $wp_admin_bar, $speedycache;

		$wp_admin_bar->add_node(array(
			'id'    => 'speedycache-toolbar-parent',
			'title' => __('SpeedyCache', 'speedycache'),
		));

		$wp_admin_bar->add_menu(array(
			'id'    => 'speedycache-toolbar-parent-delete-cache',
			'title' => __('Clear All Cache', 'speedycache'),
			'parent' => 'speedycache-toolbar-parent',
			'meta' => array('class' => 'speedycache-toolbar-child')
		));

		$wp_admin_bar->add_menu(array(
			'id'    => 'speedycache-toolbar-parent-delete-cache-and-minified',
			'title' => __('Delete Cache and Minified CSS/JS', 'speedycache'),
			'parent' => 'speedycache-toolbar-parent',
			'meta' => array('class' => 'speedycache-toolbar-child')
		));

		if(!empty($speedycache->settings['is_multi'])){
			$wp_admin_bar->add_menu(array(
				'id'    => 'speedycache-toolbar-parent-clear-cache-of-allsites',
				'title' => __('Clear Cache of All Sites', 'speedycache'),
				'parent' => 'speedycache-toolbar-parent',
				'meta' => array('class' => 'speedycache-toolbar-child')
			));
		} else {
			if(!empty(speedycache_optget('page')) && speedycache_optget('page') === 'speedycache'){
				$wp_admin_bar->add_menu(array(
					'id'    => 'speedycache-toolbar-parent-settings',
					'title' => __('Settings', 'speedycache'),
					'parent' => 'speedycache-toolbar-parent',
					'meta' => array('class' => 'speedycache-toolbar-child')
				));
			}
		}
	}

}
