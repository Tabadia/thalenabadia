<?php

namespace SpeedyCache;

if( !defined('SPEEDYCACHE_PRO_VERSION') ){
	die('HACKING ATTEMPT!');
}

class RenderBlocking {

	static function init($html){
		global $speedycache;
		
		$speedycache->render_blocking = array();
		$speedycache->render_blocking['except'] = '';
		$speedycache->render_blocking['tags'] = array();
		$speedycache->render_blocking['header_start_index'] = 0;
		$speedycache->render_blocking['js_tags_text'] = '';
		$speedycache->render_blocking['html'] = $html;
		
		self::set_header_start_index();
		self::set_tags();
		
		$speedycache->render_blocking['tags'] = self::reorder_tags($speedycache->render_blocking['tags']);
	}

	static function set_tags(){
		self::set_comments();
		self::set_js();
		self::set_css();
	}

	static function set_comments(){
		global $speedycache;
		
		$comment_tags = self::find_tags('<!--', '-->');

		self::set_except($comment_tags);

		foreach($comment_tags as $key => $value){
			if(preg_match("/\<\!--\s*\[if/i", $value['text'])){
				if(preg_match("/data-speedycache-render\=[\"\']false[\"\']/i", $value['text'])){
					continue;
				}

				array_push($speedycache->render_blocking['tags'], $value);
			}
		}
	}

	static function set_css(){
		global $speedycache;
		
		$style_tags = self::find_tags('<style', '</style>');

		foreach($style_tags as $key => $value){
			// <script>var xxx ={"id":"4", "html":"<style>\n\t\t\t.container{color:#CCCCCC;}\n\t\t<\/style>"};</script>
			if(!preg_match("/<\/script>/i", $value['text'])){
				array_push($speedycache->render_blocking['tags'], $value);
			}
		}

		$link_tags = self::find_tags('<link', '>');

		foreach($link_tags as $key => $value){
			if(preg_match("/href\s*\=/i", $value['text'])){
				if(preg_match("/rel\s*\=\s*[\'\"]\s*stylesheet\s*[\'\"]/i", $value['text'])){
					array_push($speedycache->render_blocking['tags'], $value);
				}
			}
		}
	}

	static function set_js(){
		global $speedycache;
		
		$script_tag = self::find_tags('<script', '</script>');

		foreach($script_tag as $key => $value){
			if(self::exclude_scripts($value['text'])){
				continue;
			}

			//<script type='text/javascript' src='http://partner.googleadservices.com/gampad/google_service.js'></script>
			if(preg_match("/partner\.googleadservices\.com\/gampad\/google_service\.js/i", $value['text'])){
				continue;
			}

			// <script type='text/javascript'>
			// GS_googleAddAdSenseService("ca-pub-1059380037");
			// GS_googleEnableAllServices();
			// </script>
			if(preg_match("/<script[^\>]*>\s*GS_googleAddAdSenseService\([\"\'][^\"\']+[\"\']\)\;\s*GS_googleEnableAllServices\(\)\;\s*<\/script>/i", $value['text'])){
				continue;
			}

			// <script type='text/javascript'>
			// GA_googleAddSlot("ca-pub-1059380037", "viajablog-300-250");
			// </script>
			if(preg_match("/<script[^\>]*>\s*GA_googleAddSlot\([^\)]+\)\;\s*<\/script>/i", $value['text'])){
				continue;
			}

			// <script type='text/javascript'>
			// GA_googleFetchAds();
			// </script>
			if(preg_match("/<script[^\>]*>\s*GA_googleFetchAds\(\)\;\s*<\/script>/i", $value['text'])){
				continue;
			}

			// <script>
			//   (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			//   (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			//   m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			//   })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

			//   ga('create', 'UA-9999-9', 'auto');
			//   ga('send', 'pageview');
			// </script>
			if(preg_match("/<script[^\>]*>\s*\(function\(i,s,o,g,r,a,m\)\{i\[\'GoogleAnalyticsObject\'\]/i", $value['text'])){
				if(preg_match("/ga\(\'send\',\s*\'pageview\'\)\;\s*<\/script>/i", $value['text'])){
					continue;
				}
			}

			// <script async src="https://www.googletagmanager.com/gtag/js?id=AW-123456789"></script>
			// <script>window.dataLayer=window.dataLayer||[];
			// static function speedycache_render_blocking_gtag(){
			// dataLayer.push(arguments);
			// }
			// gtag('js', new Date());
			// gtag('config', 'AW-123456789');</script>
			// <script type="text/javascript">
			// gtag('event', 'page_view', {
			// 'send_to': 'AW-123456789',
			// 'ecomm_pagetype': 'home'
			// });
			//</script>
			if(preg_match("/<script[^\>]+googletagmanager\.com\/gtag\/js[^\>]+>/i", $value['text'])){
				continue;
			}
			
			if(preg_match("/^<script>\s*window\.dataLayer\s*=\s*window\.dataLayer/i", $value['text']) && 
				preg_match("/gtag\([^\)]+\)\s*\;\s*<\/script>$/i", $value['text'])){
				continue;
			}

			/*
			<script id="bx24_form_inline" data-skip-moving="true">
				(function(w,d,u,b){w['Bitrix24FormObject']=b;w[b] = w[b] || function(){arguments[0].ref=u;
						(w[b].forms=w[b].forms||[]).push(arguments[0])};
						if(w[b]['forms']) return;
						var s=d.createElement('script');s.async=1;s.src=u+'?'+(1*new Date());
						var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
				})(window,document,'https://speedycache.bitrix24.com/bitrix/js/crm/form_loader.js','b24form');

				b24form({"id":"1","lang":"en","sec":"yesxbh","type":"inline"});
			</script>
			*/
			if(preg_match("/data-skip-moving\=[\"\']true[\"\']/i", $value['text'])){
				continue;
			}

			if(preg_match('/amzn_assoc_placement/i', $value['text'])){
				// <script>amzn_assoc_placement="adunit0";
				// amzn_assoc_search_bar="false";
				// amzn_assoc_tracking_id="3d0f1f-20";
				// amzn_assoc_ad_mode="search";
				// amzn_assoc_ad_type="smart";
				// amzn_assoc_marketplace="amazon";
				// amzn_assoc_region="US";
				// amzn_assoc_title="";
				// amzn_assoc_default_search_phrase="Spray Paint ";
				// amzn_assoc_default_category="All";
				// amzn_assoc_linkid="949bfb847147d654e679d4876a8e2b77";</script>
				continue;
			}
			
			//<script type="text/javascript">document.write("<div data-role=\"amazonjs\" data-asin=\"1234567890\" data-locale=\"JP\" data-tmpl=\"\" data-img-size=\"\" class=\"asin_1234567890_JP_ amazonjs_item\"><div class=\"amazonjs_indicator\"><span class=\"amazonjs_indicator_img\"></span><a class=\"amazonjs_indicator_title\" href=\"https://www.amazon.co.jp/%E5%B8%8%E5%AF%BF/dp/1234567890?SubscriptionId=AKIAIQGSXT2U7QVCQGHA&tag=hiyokoweb06-22&linkCode=xm2&camp=2025&creative=165953&creativeASIN=4335787\">希望難民ご一行様　ピースボートと「承認の共同体」幻想 (光文社新書)</a><span class=\"amazonjs_indicator_footer\"></span></div></div>")</script>
			if(preg_match("/^<script[^\>]*>\s*document.write\([\"\']\s*<div/i", $value['text'])){
				if(preg_match("/\s*<\/div>[\"\']\)\s*<\/script>$/i", $value['text'])){
					if(preg_match("/amazonjs/i", $value['text'])){
						continue;
					}
				}
			}

			if(preg_match('/reklamstore/i', $value['text'])){
				if(preg_match("/reklamstore_region_id/i", $value['text'])){
					continue;
				}else if(preg_match("/reklamstore\.com\/reklamstore\.js/i", $value['text'])){
					continue;
				}
			}

			//<script>document.write ('<iframe id="g2324_1" src="http://site.com/index.php?display_gallery_iframe&amp;gal_id=2324_1&amp;gal_type=2&amp;gal_cap=OFF&amp;gal_page=false"></iframe>');</script>
			if(preg_match("/document\.write\s*\(/i", $value['text'])){
				if(preg_match("/<iframe/i", $value['text'])){
					continue;
				}
			}

			//Yandex.Metrika counter
			if(preg_match("/mc\.yandex\.ru\/metrika\/watch\.js/i", $value['text'])){
				if(preg_match("/yandex_metrika_callbacks/i", $value['text'])){
					continue;
				}
			}

			//<script type="text/javascript" src="https://seal.thawte.com/getthawteseal?host_name=www.site.co.za&amp;size=S&amp;lang=en"></script>
			if(preg_match("/seal\.thawte\.com/i", $value['text'])){
				continue;
			}
			
			//<script type= "text/javascript">var RecaptchaOptions = {custom_translations : { instructions_visual : "This is my text:" }};</script>
			if(preg_match("/var\s+RecaptchaOptions\s*=\s*\{/i", $value['text'])){
				continue;
			}

			/*
			<script src='https://www.google.com/recaptcha/api.js?render=12356o4hLx1PTv7yszALlb5F_M&#038;ver=3.0'></script>
			<script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k=88a7E&amp;hl=en"></script>
			*/
			if(preg_match("/google\.com\/recaptcha\/api/i", $value['text'])){
				continue;
			}

			// <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
			// new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
			// j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
			// 'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
			// })(window,document,'script','dataLayer','GTM-5NRFSPW');</script>
			if(preg_match("/googletagmanager\.com\/gtm\.js/i", $value['text']) && preg_match("/parentNode\.insertBefore/i", $value['text'])){
				continue;
			}

			//<script src="https://gist.github.com/name/89964fb23055f8d45f12345befd4d024.js"></script>
			if(preg_match("/gist\.github\.com\//i", $value['text'])){
				continue;
			}

			//<script async="asnyc" type="text/javascript" src="https://a-ssl.ligatus.com/?ids=11111&t=js&s=1"></script>
			if(preg_match("/a-ssl\.ligatus\.com/i", $value['text'])){
				continue;
			}

			//<script type="text/javascript" src="https://sealserver.trustwave.com/seal.js?style=invert&code=89999"></script>
			if(preg_match("/sealserver\.trustwave\.com\/seal\.js/i", $value['text'])){
				continue;
			}

			//<script language="javascript" src="//inviocare.us13.list-manage.com/generate-js/?u=f3707cdf398370b05&fid=4301&show=10" type="text/javascript"></script>
			if(preg_match("/inviocare\.us13\.list\-manage\.com\/generate\-js/i", $value['text'])){
				continue;
			}

			//<script type="text/javascript" src="https://form.jotform.co/jsform/60138856"></script>
			if(preg_match("/jotform[^\/]+\/jsform\/\d+/i", $value['text'])){
				continue;
			}

			//<script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/shell.js"></script>
			//<script charset="utf-8" type="text/javascript" src="//js.hsforms.net/forms/v2.js"></script>
			if(preg_match("/js\.hsforms\.net\/forms\//i", $value['text'])){
				continue;
			}

			/*
			<script>hbspt.forms.create({
			portalId: 5102205,
			formId: "c11016e5-6a9a-4361-a358-a2ac92b8399e",
			shortcode: "wp"
			});</script>
			*/
			if(preg_match("/<script>\s*hbspt\.forms\.create\([^\)]+\)\;\s*<\/script>/i", $value['text'])){
				continue;
			}

			//<script type="application/json" class="wp-playlist-script">
			if(preg_match("/<script[^\>]+application\/json[^\>]+/i", $value['text'])){
				// if(preg_match("/<script[^\>]+wp-playlist-script[^\>]+/i", $value['text'])){
				// 	continue;
				// }

				continue;
			}

			//<script type='application/ld+json' class='yoast-schema-graph yoast-schema-graph--main'></script>
			if(preg_match("/<script[^\>]+application\/ld\+json[^\>]+>/i", $value['text'])){
				continue;
			}

			//<script id='tmpl-nf-field-input' type='text/template'>
			//<script type='text/html' class='av-video-tmpl'>
			if(preg_match("/<script[^\>]+text\/(template|html)[^\>]+/i", $value['text'])){
				continue;
			}

			//<script type="text/css" id="tmpl-tribe_customizer_css">
			if(preg_match("/<script[^\>]+text\/css[^\>]+/i", $value['text'])){
				continue;
			}

			// <script src='https://snapppt.com/widgets/widget_loader/979939cd-504c-4b59-9dcc-9e9f39dc1d09/grid.js' class='snapppt-widget'></script>
			if(preg_match("/snapppt\.com\/widgets\/widget_loader/i", $value['text'])){
				continue;
			}

			// <script src="/plugins/smart-cookie-kit/res/empty.js" data-blocked="http://maps.googleapis.com/maps/api/js" data-sck_type="2" data-sck_unlock="profiling" data-sck_ref="Google Maps" data-sck_index="1" class="BlockedBySmartCookieKit"></script>
			if(preg_match("/class\s*\=\s*[\'\"]BlockedBySmartCookieKit[\'\"]/i", $value['text'])){
				continue;
			}

			/*
			<script src="//platform.linkedin.com/in.js" type="text/javascript">lang: en_US</script>
			<script type="IN/Share" data-counter="top" data-onSuccess="share" data-url="https://helenstock.com/product/romantic-fashion/"></script>
			*/
			if(preg_match("/platform\.linkedin\.com\/in\.js/i", $value['text']) || preg_match("/type=[\"\']IN\/Share[\"\']/i", $value['text'])){
				continue;
			}

			/*
			<script id="mNCC" language="javascript">
			medianet_width="336";
			medianet_height="280";
			medianet_crid="656555462";
			medianet_versionId="3111299";
			</script>
			<script src="//contextual.media.net/nmedianet.js?cid=8CU33LCO0"></script>
			*/
			if((preg_match('/medianet_width/i', $value['text']) && preg_match('/medianet_height/i', $value['text'])) || preg_match("/contextual\.media\.net\/nmedianet\.js/i", $value['text'])){
				continue;
			}

			/*
			<script class="cmplz-stats">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||fu);</script>
			<script class="cmplz-native">static function speedycache_render_blocking_complianz_enable_cookies(){console.log("enabling cookies");}</script>
			https://wordpress.org/plugins/complianz-gdpr/
			*/
			if(preg_match("/class\s*\=\s*[\"\']cmplz-(stats|native)\s*[\"\']/i", $value['text'])){
				continue;
			}

			/*
			Advanced noCaptcha & invisible Captcha
			var anr_onloadCallback=function(){
			};
			*/
			if(preg_match('/anr_onloadCallback/i', $value['text'])){
				continue;
			}

			$speedycache->render_blocking['js_tags_text'] = $speedycache->render_blocking['js_tags_text'].$value['text'];

			array_push($speedycache->render_blocking['tags'], $value);
		}
	}

	static function set_header_start_index(){
		global $speedycache;
		
		$head_tag = self::find_tags('<head', '>');
		
		$speedycache->render_blocking['header_start_index'] = 0;
		if(!empty($head_tag[0]) && !empty($head_tag[0]['start'])){
			$speedycache->render_blocking['header_start_index'] = $head_tag[0]['start'];
		}

	}

	static function reorder_tags($tags){
		// <script>jQuery('head').append('<style>' + arr_splits[i] + '</style>');</script>
		// <script>document.getElementById("id").innerHTML='<div> <span> <!--[if !IE]>--> xxx <!--<![endif]--> </span></div>';</script>
		$list = array();
		
		for ($i=0; $i < count($tags); $i++){
			for ($j=0; $j < count($tags); $j++){ 
				if($tags[$i]['start'] > $tags[$j]['start']){
					if($tags[$i]['end'] < $tags[$j]['end']){
						array_push($list, $i);
					}
				}
			}
		}

		foreach($list as $key => $value){
			unset($tags[$value]);
		}

		$sorter = array();
		$ret = array();

		foreach($tags as $ii => $va){
			$sorter[$ii] = $va['start'];
		}

		asort($sorter);

		foreach($sorter as $ii => $va){
			$ret[$ii] = $tags[$ii];
		}

		$tags = $ret;

		return $tags;
	}

	static function exclude_scripts($text){

		$excludeables = array(
			'/google_ad_client/',
			'/googlesyndication\.com/',
			'/app\.getresponse\.com/i',
			'/adsbygoogle/i',
			'/^<script[^\>]*>\s*gtag\([^\)]+\)\s*\;\s*<\/script>$/i',
			'/smarticon\.geotrust\.com\/si\.js/i',
			'/veedi\.com\/player\/embed\/veediEmbed\.js/i',
			'/cdn\.ampproject\.org/i',
			'/data-speedycache-render\=[\"\']false[\"\']/i',
			'/adserver\.adtechjp\.com/i',
			'/ib\.3lift\.com/i',
			'/adtradradservices\.com/i',
			'/static.clickpapa.com\/c\.js/i',
			'/clickpapa_ad_client/i',
			'/cts\.tradepub\.com/i',
			'/_areklam_target|ad\.arklm\.com/i',
			'/admatic\.com\.tr/i',
			'/ca\.cubecdn\.net/i',
			'/amazon-adsystem\.com\/widgets\/onejs/i',
			'/cdn\.playwire\.com\/bolt\/js\/zeus\/embed\.js/i',
			'/static\.mailerlite\.com/i',
		);
		
		$is_excludeable = false;

		foreach($excludeables as $excludeable){
			if(preg_match($excludeable, $text)){
				$is_excludeable = true;
				break;
			}
		}

		
		return $is_excludeable;
	}

	static function set_except($tags){
		global $speedycache;
		
		foreach($tags as $key => $value){
			$speedycache->render_blocking['except'] = $value['text'].$speedycache->render_blocking['except'];
		}
	}

	static function find_tags($start_string, $end_string, $html = false){
		global $speedycache;
		
		$data = $speedycache->render_blocking['html'];
		if(!empty($html)){
			$data = $html;
		}

		$list = array();
		$start_index = false;
		$end_index = false;

		for($i = 0; $i < strlen($data); $i++){
			if(substr($data, $i, strlen($start_string)) == $start_string){
				if(!$start_index && !$end_index){
					$start_index = $i;
				}
			}

			if(empty($start_index) || $i < $start_index){
				continue;
			}

			if(substr($data, $i, strlen($end_string)) !== $end_string){
				continue;
			}
			
			$end_index = $i + strlen($end_string) - 1;
			$text = substr($data, $start_index, ($end_index - $start_index + 1));

			if($html === false){
				$tag = self::get_tags($start_index, $text, $end_index);

				if($tag !== FALSE){
					array_push($list, $tag);
				}
			}else{
				array_push($list, array('start' => $start_index, 'end' => $end_index, 'text' => $text));
			}

			$start_index = false;
			$end_index = false;
		}

		return $list;
	}

	static function get_tags($start_index, $text, $end_index){
		global $speedycache;
		
		if($start_index <= $speedycache->render_blocking['header_start_index']){
			return false;
		}
		
		if($speedycache->render_blocking['except']){
			if(strpos($speedycache->render_blocking['except'], $text) === false){
				return array('start' => $start_index, 'end' => $end_index, 'text' => $text);
			}
			
			return false;
		}
		
		return array('start' => $start_index, 'end' => $end_index, 'text' => $text);
	}

	static function split_html_condition($tag){
		if(substr_count($tag, '<!--') !== substr_count($tag, '-->')){
			return false;
		}

		if(!preg_match("/\<\!--\s*\[if[^\>]+>/i", $tag, $start_cond)){
			return false;
		}

		if(!preg_match("/<\!\[endif\]-->/i", $tag, $end_cond)){
			return false;
		}
				
		$all = array();

		$script_tag = self::find_tags('<script', '</script>', $tag);
		$style_tags = self::find_tags('<style', '</style>', $tag);
		$link_tags = self::find_tags('<link', '>', $tag);

		$all = array_merge($script_tag, $style_tags, $link_tags);

		$all = self::reorder_tags($all);

		foreach($all as $key => &$value){
			$value['text'] = $start_cond[0].$value['text'].$end_cond[0];
		}

		return $all;
	}

	static function add_defer_attr($tags){
		$external_start = false;

		foreach($tags as $key => &$value){

			if(preg_match("/^<script/i", $value['text'])){
				if(preg_match("/var\s+[^\=\;\s\"\']+\s*\=\s*new\s+[^\(\)]+\(/i", $value['text'])){
					//var block_td_uid_2_58aab3b5b4eb1=new tdBlock()
					break;
				}else if(preg_match("/jQuery\(\s*window\s*\)\.load\(/i", $value['text']) || preg_match("/\\$\(\s*window\s*\)\.load\(/i", $value['text'])){
					//jQuery(window).load(function(){
					//$(window).load(function(){
					break;
				}else if(preg_match("/^<script[^\>]*>\s*jQuery\([^\)\(]+\)\.[a-z]+\(/i", $value['text']) && strpos($value['text'], "\n") === FALSE){
					//<script>jQuery("div").append("");</script>

					$value['text'] = self::defer_load_inline_js($value['text']);
				}else if(preg_match("/^<script[^\>]*>\s*\(function\(\\$\)\{[^\}\n]+\}\)\(jQuery\)\;\s*<\/script>/", $value['text'])){
					//<script>(function($){"use strict";$("html").removeClass("ut-no-js").addClass("ut-js js");})(jQuery);</script>

					$value['text'] = self::defer_load_inline_js($value['text']);
				}else if(preg_match("/^<script[^\>]+src=[\'\"][^\>]+>/i", $value['text'])){
					if(preg_match("/data-cfasync\=/i", $value['text'])){
						break;
					}

					if(preg_match("/googletagmanager\.com/i", $value['text'])){
						continue;
					}

					if(!preg_match("/\s+defer\s+/i", $value['text'])){
						$value['text'] = preg_replace("/<script\s+/", '<script defer ', $value['text']);
					}

					$external_start = true;
				}else{
					//inline js

					if(preg_match("/connect\.facebook\.net/i", $value['text']) && preg_match("/parentNode\.insertBefore/i", $value['text'])){
						// <script>(function(d, s, id){
						// var js, fjs=d.getElementsByTagName(s)[0];
						// if(d.getElementById(id)) return;
						// js=d.createElement(s); js.id=id;
						// js.async=true;
						// js.src="//connect.facebook.net/nl_NL/sdk.js#xfbml=1&version=v2.8&appId=1126044540802926";
						// fjs.parentNode.insertBefore(js, fjs);
						// }(document, 'script', 'facebook-jssdk'));</script>

						continue;
					}else if(preg_match("/<script[^\>]*>\s*_stq\s*\=\s*window\._stq/i", $value['text']) && preg_match("/_stq\.push\s*\([^\)]+\)\s*\;\s*<\/script>/i", $value['text'])){
						// <script type='text/javascript'>
						// _stq = window._stq || [];
						// _stq.push([ 'view', {v:'ext',j:'1:5.5',blog:'121052134',post:'88',tz:'3',srv:'www.bibersa.com'} ]);
						// _stq.push([ 'clickTrackerInit', '121052134', '88' ]);
						// </script>

						continue;
					}else{
						if($external_start){
							break;
						}else{
							if(preg_match("/var\s+wpforms(_settings|RecaptchaLoad|RecaptchaCallback)\s*\=/i", $value['text'])){
								// WPForms Lite
								// var wpforms_settings = {variable}
								// var wpformsRecaptchaLoad=function(){
								// var wpformsRecaptchaCallback=function
								continue;
							}

							if(preg_match("/window\.TL_Const/i", $value['text']) && preg_match("/var\s+TL_Const/i", $value['text'])){
								// Thrive Leads
								// if(!window.TL_Const){var TL_Const=
								continue;
							}

							if(preg_match("/<script[^\>]+application\/ld\+json[^\>]+>/i", $value['text'])){
								// <script type="application/ld+json">
								// {
								// "@context": "http://schema.org/",
								// "@type": "Product",
								// "name": "Product Name",
								// "image": "/default.png",
								// "aggregateRating": {
								// "@type": "AggregateRating",
								// "ratingValue": "5",
								// "reviewCount": "81"
								// }
								// }
								// </script>
								continue;
							}

							if(preg_match("/_translator_revolution_dropdown/i", $value['text'])){
								// <script>var _translator_revolution_dropdown=_translator_revolution_dropdown||{languages: ["en","fr","de"],excludeSelector: "code, #wpadminbar",locationWidget: false};</script>

								continue;
							}

							$value['text'] = self::defer_load_inline_js($value['text']);
						}
					}
				}
			}else if(preg_match("/^<(link|style)/i", $value['text'])){
				continue;
			}else if(preg_match("/<\!--\s*\[if/i", $value['text'])){
				preg_match_all("/<script[^\>]+src=[\'\"][^\>]+>/i", $value['text'], $src_number);
				preg_match_all("/<script[^\>]*/i", $value['text'], $script_tag_number);

				if(count($script_tag_number[0]) != count($src_number[0])){
					break;
				}

				if(preg_match("/<link|<style/i", $value['text'])){
					break;
				}

				if(!preg_match("/<script[^\>]+src=[\'\"][^\>]+>/i", $value['text'])){
					break;
				}

				if(preg_match("/data-cfasync\=/i", $value['text'])){
					break;
				}

				if(!preg_match("/<script[^\>]+defer[^\>]+>/i", $value['text'])){
					$value['text'] = preg_replace("/<script\s+/", '<script defer ', $value['text']);
				}
			}else{
				break;
			}
		}

		return $tags;
	}

	static function action($render_blocking_css = false, $make_defer = false){
		global $speedycache;
		
		$wpemojiSettings = '';
		$google_fonts = '';
		$bootstrapcdn = '';
		$inline_js = '';
		$document_ready_js = '';
		$third_part_js = '';
		$script = '';
		$style = '';
		
		//to remove tags
		$speedycache->render_blocking['tags'] = array_reverse($speedycache->render_blocking['tags']);
		
		foreach($speedycache->render_blocking['tags'] as $key => &$value){
			if(preg_match("/\<\!--\s*\[if[^\>]+>/i", $value['text'])){
				if($arr = self::split_html_condition($value['text'])){
					$style = '';
					$script = '';

					foreach($arr as $arr_key => $arr_value){
						if(preg_match("/\<\!--\s*\[if[^\>]+>(<link|<style)/i", $arr_value['text'])){
							$style = $style."\n".$arr_value['text'];
						}else if(preg_match("/\<\!--\s*\[if[^\>]+><script/i", $arr_value['text'])){
							$script = $script."\n".$arr_value['text'];
						}
					}
				}

				$value['text'] = $script;
				$speedycache->render_blocking['html'] = substr_replace($speedycache->render_blocking['html'], $style, $value['start'], ($value['end'] - $value['start'] + 1));
			}else if(preg_match("/^<script/i", $value['text'])){
				$speedycache->render_blocking['html'] = substr_replace($speedycache->render_blocking['html'], '', $value['start'], ($value['end'] - $value['start'] + 1));
			}else if(preg_match("/^<link[^\>]+(fonts|ajax)\.googleapis\.com[^\>]+>/", $value['text'])){
				if(!empty($speedycache->options['google_fonts'])){
					$speedycache->render_blocking['html'] = substr_replace($speedycache->render_blocking['html'], '', $value['start'], ($value['end'] - $value['start'] + 1));

					$google_fonts = $value['text']."\n".$google_fonts;
				}
			}else if(preg_match("/^<link[^\>]+(maxcdn)\.bootstrapcdn\.com[^\>]+>/", $value['text'])){
				$speedycache->render_blocking['html'] = substr_replace($speedycache->render_blocking['html'], '', $value['start'], ($value['end'] - $value['start'] + 1));

				$bootstrapcdn = $value['text']."\n".$bootstrapcdn;
			}
		}
		
		foreach($speedycache->render_blocking['tags'] as $key => &$value){
			
			if($value['text'] && preg_match("/^<script/i", $value['text'])){
				if(preg_match("/gravatar\.com\/js\/gprofiles\.js/i", $value['text']) || 
					preg_match("/use\.fontawesome\.com/i", $value['text']) || 
					preg_match("/s0\.wp\.com\/".SPEEDYCACHE_WP_CONTENT_DIR."\/js\/devicepx-jetpack\.js/i", $value['text'])){
					//<script type='text/javascript' src='http://s.gravatar.com/js/gprofiles.js?ver=2023Janaa'></script>
					//<script type='text/javascript' src='http://s0.wp.com/site-data/js/devicepx-jetpack.js?ver=201701'></script>
					//<script defer src="https://use.fontawesome.com/123456789.js"></script>

					if(!preg_match("/\sdefer\s/i", $value['text'])){
						$value['text'] = preg_replace("/<script\s+/", '<script defer ', $value['text']);
					}

					if(!preg_match("/\sasync\s/i", $value['text'])){
						$value['text'] = preg_replace("/<script\s+/", '<script async ', $value['text']);
					}

					unset($speedycache->render_blocking['tags'][$key]);
					$third_part_js = $value['text']."\n".$third_part_js;
				}else if(preg_match("/document\.addEventListener\(\s*[\"\']\s*DOMContentLoaded\s*[\"\']\s*,\s*function\s*\([^\)]*\)\s*\{/i", $value['text'])){
					//to remove jQuery(document) which contains window.attachEvent
					unset($speedycache->render_blocking['tags'][$key]);
					$document_ready_js = $value['text']."\n".$document_ready_js;
				}else if(preg_match("/^<script[^\>]*>\s*jQuery\(\s*document\s*\)\.ready\(/i", $value['text'])){
					// <script>
					// 	jQuery(document).ready(function($){
					// 	});
					// </script>

					if(preg_match("/jQuery\(\s*window\s*\)\.load\(/i", $value['text'])){
						//jQuery(window).load(function(){
						continue;
					}

					if(preg_match("/\\$\(\s*window\s*\)\.load\(/i", $value['text'])){
						//$(window).load(function(){
						continue;
					}

					if(preg_match("/var owl\s*=\s*\\$\([\'\"]\.products-carousels-/i", $value['text'])){
						//to exclude carousel of Master Slider
						//https://codecanyon.net/item/master-slider-wordpress-responsive-touch-slider
						//var owl=$('.products-carousels-99056136 #products');
						continue;
					}
					
					if(preg_match("/\}\s*\)\s*\;\s*<\/script>$/i", $value['text'])){
						$jqIsReady_func_name = 'jqIsReady_'.rand(100,999);
						$value['text'] = preg_replace("/(<script[^\>]*>)/i", "$1"."(static function speedycache_render_blocking_".$jqIsReady_func_name."(){if(typeof jQuery === \"undefined\"){".$jqIsReady_func_name."();}else{", $value['text']);
						$value['text'] = preg_replace("/\s*(<\/script>)/i", "}})();"."$1", $value['text']);

						unset($speedycache->render_blocking['tags'][$key]);
						$document_ready_js = $value['text']."\n".$document_ready_js;
					}
				}else if(preg_match("/^<script[^\>]*>\s*window\.\_wpemojiSettings/", $value['text'])){
					//to remove window._wpemojiSettings from tags
					unset($speedycache->render_blocking['tags'][$key]);
					$wpemojiSettings = $wpemojiSettings."\n".$value['text'];
				}else if(!preg_match("/^<script[^\>]+src=[\'\"][^\>]+>/", $value['text'])){
					//to remove inline js which do not contain any function
					//<script>var _wpcf7={"loaderUrl":"sample"};</script>
					$tmp = $value['text'];

					// <script>/*<![CDATA[*/var THO_Front = THO_Front || {}; THO_Front.data = {"end_of_content_id":"tho-end","const":{"_e_click":1,"_engagement":2}}/*]]> */</script>
					$tmp = preg_replace("/<script[^\>]+>\s*\/\*\s*<\!\[CDATA\[\s*\*\//", "", $tmp);
					$tmp = preg_replace("/\/\*\s*\]\]>\s*\*\/\s*<\/script>/", "", $tmp);

					// to remove multi-line comments but it removes everything. it does not work properly
					$tmp = preg_replace("/\s*\/\*(.+)\*\/\s*/", '', $tmp);

					//var themifyScript causes "fixed header" issue on thepurplepumpkinblog.co.uk
					if(preg_match("/var\sthemifyScript/i", $tmp)){
						continue;
					}

					//<script data-cfasync="false" type="text/javascript">var lsjQuery = jQuery;</script>
					if(preg_match("/data-cfasync\=[\"\']false[\"\']/i", $tmp)){
						continue;
					}

					if(preg_match("/(function|jQuery|if)\s*\([^\)\(]+\)/i", $tmp)){
						if(!preg_match("/<script[^\>]*>\s*static function speedycache_render_blocking_heateorSssLoadEvent/i", $tmp)){
							//https://plugins.trac.wordpress.org/browser/sassy-social-share/trunk/includes/class-sassy-social-share-widgets.php#L72
							//https://plugins.trac.wordpress.org/browser/sassy-social-share/trunk/public/class-sassy-social-share-public.php#L104

							continue;
						}
					}

					//var block_td_uid_2_58aab3b5b4eb1=new tdBlock()
					if(preg_match("/var\s+[^\=\;\s\"\']+\s*\=\s*new\s+[^\(\)]+\(/i", $tmp)){
						continue;
					}

					//<script>var lsjQuery = jQuery;</script>
					if(preg_match("/var\s+[^\=\s]+\s*\=\s*jQuery\s*\;/i", $value['text'])){
						continue;
					}

					//<script>Abtf.css();</script>
					//<script>dtGlobals.logoEnabled=1;</script>
					//if var does not exist
					if(!preg_match("/var\s*[a-z0-9_]+\s*\=\s*[^\;]+\s*\;/i", $tmp)){
						continue;
					}

					//$=jQuery.noConflict()
					//var joblistin_caned_msgs=jQuery.parseJSON('[{\"title\":\"jobs description 1\",
					if(preg_match("/\=\s*jQuery\.(noConflict|parseJSON)\(/i", $tmp)){
						continue;
					}

					//$(".menu-item-has-children a")
					if(preg_match("/\\\$\s*\(\s*[\"\']/", $tmp)){
						continue;
					}

					/*
					<script>
					jQ_nxs(document).on('nxs_event_resizeend.menu_mini_expand', function(){
						//something
					});
					</script>
					*/
					if(preg_match("/\([^\)]+\)\.on\(\s*[\'\"][^\'\"]+[\'\"]\s*\,\s*function\(\)\{/", $tmp)){
						continue;
					}

					/*
					<script>
					videojs("vid1").videoJsResolutionSwitcher();
					var my_video_id = videojs("vid1");
					my_video_id.watermark({ file: "/site-data/uploads/2019/05/logo.png", xpos: 0, ypos: 0, xrepeat: 0, opacity: 1,clickable: true,url: "https://www.site.net"  });
					</script>
					*/
					if(preg_match("/videojs\([^\)\(]+\)\.videoJsResolutionSwitcher/", $tmp)){
						continue;
					}

					unset($speedycache->render_blocking['tags'][$key]);
					$inline_js = $value['text']."\n".$inline_js;

				}
			}
		}

		//to add Google Fonts at the end of page before js sources
		if(!empty($google_fonts)){
			//$google_fonts = self::combine_google_fonts($google_fonts);

			if(!empty($speedycache->options['google_fonts'])){
				
				if(preg_match('/speedycache-google-fonts/', $speedycache->render_blocking['html'])){
					$speedycache->render_blocking['html'] = str_replace('<noscript id="speedycache-google-fonts">', '<noscript id="speedycache-google-fonts">'.$google_fonts, $speedycache->render_blocking['html']);
				}else{
					$google_fonts = self::async_google_fonts($google_fonts);
					$speedycache->render_blocking['html'] = str_replace('</body>', $google_fonts."\n".'</body>', $speedycache->render_blocking['html']);
				}

			}
		}
		
		//to add BootstrapCDN at the end of page before js sources
		if(!empty($bootstrapcdn)){
			$speedycache->render_blocking['html'] = str_replace('</body>', $bootstrapcdn."\n".'</body>', $speedycache->render_blocking['html']);
		}

		//to add Inline Js before at the end of page before js sources
		if(!empty($inline_js)){
			$speedycache->render_blocking['html'] = str_replace('</body>', $inline_js."\n".'</body>', $speedycache->render_blocking['html']);
		}

		//to add third_part_js at the end of page
		if(!empty($third_part_js)){
			$speedycache->render_blocking['html'] = str_replace('</body>', $third_part_js."\n".'</body>', $speedycache->render_blocking['html']);
		}

		//to add defer and async attribute
		if(!empty($make_defer) || empty($speedycache->options['combine_js_enhanced'])){
			$speedycache->render_blocking['tags'] = self::add_defer_attr($speedycache->render_blocking['tags']);
		}

		//to add tags into footer
		$speedycache->render_blocking['tags'] = array_reverse($speedycache->render_blocking['tags']);
		foreach($speedycache->render_blocking['tags'] as $key => $value){
			if(preg_match("/^<script/i", $value['text'])){
				$speedycache->render_blocking['html'] = str_replace('</body>', $value['text']."\n".'</body>', $speedycache->render_blocking['html']);
			}else if(preg_match("/\<\!--\s*\[if[^\>]+>/i", $value['text'])){
				$speedycache->render_blocking['html'] = str_replace('</body>', $value['text']."\n".'</body>', $speedycache->render_blocking['html']);
			}
		}

		//to add document_ready_js at the end of page
		if(!empty($document_ready_js)){
			$speedycache->render_blocking['html'] = str_replace('</body>', $document_ready_js."\n".'</body>', $speedycache->render_blocking['html']);
		}

		//to add wpemojiSettings at the end of page
		if(!empty($wpemojiSettings)){
			$speedycache->render_blocking['html'] = str_replace('</body>', $wpemojiSettings."\n".'</body>', $speedycache->render_blocking['html']);
		}
		
		return preg_replace("/^\s+/m", '', $speedycache->render_blocking['html']);
	}

	static function async_google_fonts($fonts){
		if(preg_match("/\shref\=/", $fonts)){
			$fonts = '<noscript id="speedycache-google-fonts">'.$fonts.'</noscript>';
			$onload = "<script>document.addEventListener('DOMContentLoaded',function(){function speedycache_render_blocking_speedycachegl(){var wgh=document.querySelector('noscript#speedycache-google-fonts').innerText, wgha=wgh.match(/<link[^\>]+>/gi);var i=0;for(i=0;i<wgha.length;i++){var wrpr=document.createElement('div');wrpr.innerHTML=wgha[i];document.body.appendChild(wrpr.firstChild);}}speedycache_render_blocking_speedycachegl();});</script>";
			
			return $fonts."\n".$onload;
		}
		
		return $fonts;
	}

	static function combine_google_fonts($fonts){
		$family = '';
		$subset = '';

		preg_match_all("/fonts\.googleapis\.com\/css\?family\=([^\'\"]+)/si", $fonts, $arr);

		if(empty($arr[0])){
			return $fonts;
		}

		foreach($arr[0] as $key => $value){
			//to remove special chars
			$value = htmlspecialchars_decode($value);

			$parts = parse_url($value);
			parse_str($parts['query'], $query);

			$family = $query['family'];
			if(!empty($family)){
				$family = $family.'|'.$query['family'];
			}

			if(isset($query['subset']) && $query['subset']){
				
				$subset = $query['subset'];
				if(!empty($subset)){
					$subset = $subset.','.$query['subset'];
				}
			}
		}

		$family = str_replace(' ', '+', $family);
		if(!empty($subset)){
			$family.'&subset='.$subset;
		}

		return "<!--\n".$fonts."\n-->\n"."<link rel='stylesheet' id='speedycache-google-combined' href='http://fonts.googleapis.com/css?family=".$family."' type='text/css' media='all'/>";
	}

	static function defer_load_inline_js($script){
		if(preg_match("/<script[^\>]*>\s*\/\/\<\!\[CDATA\[\s*/", $script) && preg_match("/\/\/\]\]\>\s*<\/script>/", $script)){
			$script = preg_replace("/(<script[^\>]*>)\s*\/\/\<\!\[CDATA\[\s*/", "$1\n", $script);
			$script = preg_replace("/\/\/\]\]\>\s*(<\/script>)/", "\n$1", $script);
		}

		if(preg_match("/var\s+wpforms_conditional_logic/i", $script)){
			// to exclude the conditions of wpforms
			// <script type='text/javascript'>
			// /* <![CDATA[ */
			// var wpforms_conditional_logic = {"5616":{"17"}]],"action":"show"}}}
			// /* ]]> */
			// </script>
			return $script;
		}

		if(preg_match("/var\s+recaptchaWidgets/i", $script)){
			if(preg_match("/var\s+recaptchaCallback/i", $script)){
				// to exclude
				// https://github.com/IQComputing/wpcf7-recaptcha/blob/master/recaptcha-v2.php#L94
				return $script;
			}
		}

		if(preg_match('/static function speedycache_render_blocking_jqIsReady/i', $script)){
			return $script;
		}

		if(preg_match("/^<script[^\>]+>\s*<\/script>/i", $script)){
			// to exclude empty inline source
			// <script type="text/javascript" id="speedycachep-script-frontend"></script>
			return $script;
		}

		$script = preg_replace("/^(<script[^\>]*>)/i", "$1"."document.addEventListener('DOMContentLoaded',function(){", $script);
		$script = preg_replace("/\s*(<\/script>)/i", "});"."$1", $script);

		return $script;
	}
}