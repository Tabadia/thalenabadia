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

class Mobile{

	static function cache(){
		global $speedycache;
		
		$speedycache->mobile_cache = array();
		$speedycache->mobile_cache['folder_name'] = 'mobile-cache';
		$speedycache->mobile_cache['wptouch'] = false;
	}

	static function update_htaccess($data){
		global $speedycache;
		
		preg_match("/RewriteEngine\sOn(.+)/is", $data, $out);
		$htaccess = "\n##### Start: Mobile Cache Rules #####\n";
		$htaccess .= $out[0];

		// Updates the moble cache htaccess rule according to wptouch
		if($speedycache->mobile_cache['wptouch']){
			$wptouch_rule = "RewriteCond %{HTTP:Cookie} !wptouch-pro-view=desktop";
			$htaccess = str_replace("RewriteCond %{HTTP:Profile}", $wptouch_rule."\n"."RewriteCond %{HTTP:Profile}", $htaccess);
		}

		/**
		* Structure of this array is
		*	searchable => replacer
		*/
		$rules = array(
			'RewriteCond %{HTTP:Cookie} !safirmobilswitcher=mobil' => 'RewriteCond %{HTTP:Cookie} !safirmobilswitcher=masaustu',
			'RewriteCond %{HTTP_USER_AGENT} !^.*' => 'RewriteCond %{HTTP_USER_AGENT} ^.*',
		);
		
		foreach($rules as $search => $replacement){
			$htaccess = str_replace($search, $replacement, $htaccess);
		}

		$htaccess = preg_replace('/\/speedycache\/'.preg_quote(SPEEDYCACHE_SERVER_HOST).'\/all\//', '/speedycache/'.SPEEDYCACHE_SERVER_HOST .'/'. $speedycache->mobile_cache['folder_name']."/", $htaccess);
		$htaccess .= "\n##### End: Mobile Cache Rules #####\n";

		return $htaccess;
	}

}
