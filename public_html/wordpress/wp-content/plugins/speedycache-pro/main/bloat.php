<?php

namespace SpeedyCache;

if(!defined('ABSPATH')){
	die('HACKING ATTEMPT!');
}

class Bloat{

	static function actions(){
		global $speedycache;
		
		// Add the filters / actions
		if(!empty($speedycache->bloat['disable_xmlrpc'])){
			add_filter('xmlrpc_enabled', '__return_null');
			add_filter('bloginfo_url', '\SpeedyCache\Bloat::xmlrpc_remove_pingback_url', 10000, 2);
			add_action('wp_loaded', '\SpeedyCache\Bloat::xmlrpc_disable');
		}
		
		// Disable DashIcons
		if(!empty($speedycache->bloat['disable_dashicons'])){
			add_action('wp_print_styles', '\SpeedyCache\Bloat::disable_dashicons');
		}
		
		if(!is_admin()){
			// Remove jQuery migrate
			if(!empty($speedycache->bloat['disable_jmigrate'])){
				add_action('wp_default_scripts', '\SpeedyCache\Bloat::remove_jquery_migrate');
			}
			
			// Remove Block CSS
			if(!empty($speedycache->bloat['disable_block_css'])){
				add_action('wp_enqueue_scripts', '\SpeedyCache\Bloat::disable_block_editor_css');
			}

			// Disable Cart Fragment
			if(!empty($speedycache->bloat['disable_cart_fragment'])){
				add_action('wp_enqueue_scripts', '\SpeedyCache\Bloat::disable_cart_fragment', 11);
			}
			
			// Disable WooCommerce Assets
			if(!empty($speedycache->bloat['disable_woo_assets'])){
				add_action('wp_enqueue_scripts', '\SpeedyCache\Bloat::disable_woocommerce_assets', 99);
			}
			
			// Disale RSS Feeds
			if(!empty($speedycache->bloat['disable_rss'])){
				add_action('do_feed_rdf', '\SpeedyCache\Bloat::disable_wp_feeds', 1);
				add_action('do_feed_rss', '\SpeedyCache\Bloat::disable_wp_feeds', 1);
				add_action('do_feed_rss2', '\SpeedyCache\Bloat::disable_wp_feeds', 1);
				add_action('do_feed_atom', '\SpeedyCache\Bloat::disable_wp_feeds', 1);
				add_action('do_feed_rss2_comments', '\SpeedyCache\Bloat::disable_wp_feeds', 1);
				add_action('do_feed_atom_comments', '\SpeedyCache\Bloat::disable_wp_feeds', 1);
				
				// Remove links
				remove_action( 'wp_head', 'feed_links_extra', 3 );
				remove_action( 'wp_head', 'feed_links', 2 );
			}
		}

		// Disable OEmbeds
		if(!empty($speedycache->bloat['disable_oembeds'])){
			add_action('init', '\SpeedyCache\Bloat::disable_oembeds');
		}
		
		if(!empty($speedycache->bloat['disable_gutenberg'])){
			add_filter('use_block_editor_for_post_type', '__return_false', 100);
			add_filter('after_setup_theme', '\SpeedyCache\Bloat::disable_gutenberg_hooks');
		}
		
		// Limit Post revisions
		if(!empty($speedycache->bloat['limit_post_revision'])){
			add_filter('wp_revisions_to_keep', '\SpeedyCache\Bloat::limit_post_revisions');
		}

		// Update Heartbeat
		if(!empty($speedycache->bloat['update_heartbeat'])){
			add_action('init', '\SpeedyCache\Bloat::disable_heartbeat');
			add_action('wp_enqueue_scripts', '\SpeedyCache\Bloat::disable_heartbeat');
			add_action('admin_enqueue_scripts', '\SpeedyCache\Bloat::disable_heartbeat');
			add_filter('heartbeat_settings', '\SpeedyCache\Bloat::change_heartbeat_interval', 100);
		}
	}

	// Disbale XML request
	static function xmlrpc_disable(){
		global $pagenow;

		// Is it xmlrpc.php ?
		if ($pagenow === 'xmlrpc.php'){	
			echo 'XML-RPC is disabled';
			exit();
		}	
	}
	
	// Disables the XML-RPC functionality
	static function xmlrpc_remove_pingback_url($output, $show) {

		if($show == 'pingback_url'){
			$output = '';
		}

		return $output;
	}
	
	// Disable Dashicons
	static function disable_dashicons(){
		if(!is_admin_bar_showing() && !is_customize_preview()){
			wp_dequeue_style('dashicons');
			wp_deregister_style('dashicons');
		}
	}
	
	// Remove jQuery Migrate
	static function remove_jquery_migrate($scripts){
		
		if(!is_admin() && isset($scripts->registered['jquery'])){
			$script = $scripts->registered['jquery'];

			if($script->deps){
				$script->deps = array_diff($script->deps, array('jquery-migrate'));
			}
		}
	}
	
	// Disable OEmbeds
	static function disable_oembeds(){
		// Remove oEmbed REST API endpoint
		remove_action('rest_api_init', 'wp_oembed_register_route');

		// Disable oEmbed auto-discovery links
		remove_action('wp_head', 'wp_oembed_add_discovery_links');

		// Disable oEmbed-specific JavaScript from the front-end and back-end
		remove_action('wp_head', 'wp_oembed_add_host_js');

		// Remove oEmbed provider fetch URL rewriting
		remove_filter('oembed_fetch_url', 'wp_oembed_rewrite_url');

		// Disable oEmbed in TinyMCE editor
		add_filter('tiny_mce_plugins', '\SpeedyCache\Bloat::disable_tiny_mce_oembed');
	}

	static function disable_tiny_mce_oembed($plugins){
		return array_diff($plugins, array('wpembed'));
	}

	// Remove Block Editor CSS
	static function disable_block_editor_css(){
		wp_dequeue_style('wp-block-library');
		wp_dequeue_style('wp-block-library-theme');
		wp_dequeue_style('wp-block-style');
	}
	
	// Updates the count of number of post revesions.
	static function limit_post_revisions($num){
		global $speedycache;
		
		if(!empty($speedycache->bloat['post_revision_count']) && $speedycache->bloat['post_revision_count'] === 'disable'){
			$num = 0;
		} elseif(!empty($speedycache->bloat['post_revision_count']) && is_numeric($speedycache->bloat['post_revision_count'])){
			$num = intval($speedycache->bloat['post_revision_count']);
		}

		return $num;
	}
	
	// Updating the Heartbeat interval.
	static function change_heartbeat_interval($settings){
		global $speedycache;

		if(!empty($speedycache->bloat['heartbeat_frequency'])){
			$settings['interval'] = $speedycache->bloat['heartbeat_frequency'];
			$settings['minimalInterval'] = $speedycache->bloat['heartbeat_frequency'];
		}

		return $settings;
	}
	
	static function disable_cart_fragment(){
		if(function_exists('is_woocommerce')){
			if(!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page() && !is_product() && !is_product_category() && !is_shop()){
				wp_dequeue_script('wc-cart-fragments');
			}
		}
	}
	
	static function disable_woocommerce_assets(){
		if(!class_exists('WooCommerce')){
			return;
		}
		
		if(!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page() && !is_product() && !is_product_category() && !is_shop()){
			// Disable WooCommerce stylesheets
			wp_dequeue_style('woocommerce-general');
			wp_dequeue_style('woocommerce-layout');
			wp_dequeue_style('woocommerce-smallscreen');
			wp_dequeue_style('woocommerce_frontend_styles');
			wp_dequeue_style('woocommerce_fancybox_styles');
			wp_dequeue_style('woocommerce_chosen_styles');
			wp_dequeue_style('woocommerce_prettyPhoto_css');

			// Disable WooCommerce scripts
			wp_dequeue_script('wc_price_slider');
			wp_dequeue_script('wc-single-product');
			wp_dequeue_script('wc-add-to-cart');
			wp_dequeue_script('wc-checkout');
			wp_dequeue_script('wc-add-to-cart-variation');
			wp_dequeue_script('wc-single-product');
			wp_dequeue_script('wc-cart');
			wp_dequeue_script('wc-chosen');
			wp_dequeue_script('woocommerce');
			wp_dequeue_script('prettyPhoto');
			wp_dequeue_script('prettyPhoto-init');
			wp_dequeue_script('jquery-blockui');
			wp_dequeue_script('jquery-placeholder');
			wp_dequeue_script('fancybox');
			wp_dequeue_script('jqueryui');
		}
	}
	
	static function disable_wp_feeds(){
		 wp_die(sprintf(esc_html__('No feed available, please visit our %1$shomepage%2$s!'),
            ' <a href="' . esc_url( home_url( '/' ) ) . '">',
            '</a>'));
	}
	
	static function disable_heartbeat(){
		global $speedycache, $pagenow;

		if(empty($speedycache->bloat['disable_heartbeat'])) {
			return;
		}

		$remove_heartbeat = false;

		switch($speedycache->bloat['disable_heartbeat']){
			case 'disable':
				$remove_heartbeat = true;
				break;
				
			case 'editor':
				if($pagenow != 'post.php' && $pagenow != 'post-new.php'){
					$remove_heartbeat = true;
				}
		}
		
		if(!empty($remove_heartbeat)){
			wp_deregister_script('heartbeat');
		
			//We have replaced heartbeat with an empty heartbeat to prevent any errors
			wp_enqueue_script('heartbeat', SPEEDYCACHE_PRO_URL . '/assets/js/heartbeat.js', null, SPEEDYCACHE_PRO_VERSION, true);
		}
	}

	// Disable Gutenberg
	static function disable_gutenberg_hooks(){
		remove_action('admin_menu', 'gutenberg_menu');
		remove_action('admin_init', 'gutenberg_redirect_demo');

		remove_filter('wp_refresh_nonces', 'gutenberg_add_rest_nonce_to_heartbeat_response_headers');
		remove_filter('get_edit_post_link', 'gutenberg_revisions_link_to_editor');
		remove_filter('wp_prepare_revision_for_js', 'gutenberg_revisions_restore');

		remove_action('rest_api_init', 'gutenberg_register_rest_routes');
		remove_action('rest_api_init', 'gutenberg_add_taxonomy_visibility_field');
		remove_filter('rest_request_after_callbacks', 'gutenberg_filter_oembed_result');
		remove_filter('registered_post_type', 'gutenberg_register_post_prepare_functions');

		remove_action('do_meta_boxes', 'gutenberg_meta_box_save', 1000);
		remove_action('submitpost_box', 'gutenberg_intercept_meta_box_render');
		remove_action('submitpage_box', 'gutenberg_intercept_meta_box_render');
		remove_action('edit_page_form', 'gutenberg_intercept_meta_box_render');
		remove_action('edit_form_advanced', 'gutenberg_intercept_meta_box_render');
		remove_filter('redirect_post_location', 'gutenberg_meta_box_save_redirect');
		remove_filter('filter_gutenberg_meta_boxes', 'gutenberg_filter_meta_boxes');

		remove_action('admin_notices', 'gutenberg_build_files_notice');
		remove_filter('body_class', 'gutenberg_add_responsive_body_class');
		remove_filter('admin_url', 'gutenberg_modify_add_new_button_url'); // old
		remove_action('admin_enqueue_scripts', 'gutenberg_check_if_classic_needs_warning_about_blocks');
		remove_filter('register_post_type_args', 'gutenberg_filter_post_type_labels');

		remove_action('admin_init', 'gutenberg_add_edit_link_filters');
		remove_action('admin_print_scripts-edit.php', 'gutenberg_replace_default_add_new_button');
		remove_filter('redirect_post_location', 'gutenberg_redirect_to_classic_editor_when_saving_posts');
		remove_filter('display_post_states', 'gutenberg_add_gutenberg_post_state');
		remove_action('edit_form_top', 'gutenberg_remember_classic_editor_when_saving_posts');
	}
}
