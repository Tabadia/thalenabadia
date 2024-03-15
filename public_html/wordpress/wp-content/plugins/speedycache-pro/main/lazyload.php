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

class LazyLoad{
	static function init(){
		global $speedycache;
		
		$speedycache->lazy_load = array();
		$speedycache->lazy_load['exclude'] = array();
		$speedycache->lazy_load['host'] = '';
		$speedycache->lazy_load['placeholder'] = '';
		$url = parse_url(site_url());
		$speedycache->lazy_load['host'] = $url['host'];

		if(!empty($speedycache->options['lazy_load_keywords']) && $speedycache->options['lazy_load_keywords']){
			$speedycache->lazy_load['exclude'] = explode(',', $speedycache->options['lazy_load_keywords']);
		}
		
		self::set_placeholder();
	}

	static function set_placeholder(){
		global $speedycache;
		
		if(!empty($speedycache->options['lazy_load_placeholder']) && $speedycache->options['lazy_load_placeholder']){
			
			if($speedycache->options['lazy_load_placeholder'] === 'default'){
				$speedycache->lazy_load['placeholder'] = SPEEDYCACHE_PRO_URL . '/assets/images/image-palceholder.png';
			} else if($speedycache->options['lazy_load_placeholder'] === 'base64'){
				$speedycache->lazy_load['placeholder'] = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
			} else {
				$speedycache->lazy_load['placeholder'] = preg_replace('/^custom_/', '', $speedycache->options['lazy_load_placeholder']);
			}
			
			return;
		} 
		
		$speedycache->lazy_load['placeholder'] = SPEEDYCACHE_PRO_URL . '/assets/images/image-palceholder.png';
		return;
	}

	static function instagram($tag){
		$src = false;

		if(preg_match("/src\s*\=\s*[\"\']([^\"\']+sb-instagram\.min\.js)/", $tag, $out)){
			$src = $out[1];
		}

		if(empty($src)){
			return $tag;
		}

		$tmp_script = '<script type="text/javascript">'.
		"window.addEventListener('scroll',function(){".
		"(function(d,s){".
		"if(document.querySelectorAll("."\"script[src='\""." + s + "."\"']\"".").length > 0){return;}".
		"var t = d.createElement('script');".
		't.setAttribute("src", s);'.
		"d.body.appendChild(t);".
		'})(document, "'.$src.'");'.
		"});".
		"</script>\n";

		return $tmp_script;
	}

	static function mark_images($content){
		global $speedycache;

		if(speedycache_is_mobile()){
			return $content;
		}

		preg_match_all('/<img[^\>]+>/i', $content, $matches);

		if(count($matches[0]) <= 0){
			return $content;
		}

		foreach($matches[0] as $img){
			if(self::is_thumbnail($img) || self::is_third_party($img) || !self::is_full($img)){
				continue;
			}

			$tmp_img = $img;

			if(strpos($img, 'decoding') === FALSE){
				$tmp_img = preg_replace("/<img\s/", '<img decoding="async" ', $img);
			}

			$tmp_img = preg_replace("/<img\s/", '<img speedycache-lazyload-disable="true" ', $tmp_img);

			$content = str_replace($img, $tmp_img, $content);
		}

		return $content;
	}

	static function mark_attachment_page_images($attr, $attachment){

		if(speedycache_is_mobile()){
			return $attr;
		}

		if(isset($attr['src'])){
			if(self::is_thumbnail($attr['src'])){
				return $attr;
			}

			if(self::is_third_party($attr['src'])){
				return $attr;
			}

			if(!self::is_full('<img src="' . $attr['src'] . '" class="' . $attr['class'] . '">')){
				return $attr;
			}
		}

		if(empty($attachment)){
			return $attr;
		}

		$attr['speedycache-lazyload-disable'] = 'true';

		return $attr;
	}

	static function is_thumbnail($src){
		
		$resolution_pregs = array(
			'/\-[12]\d{0,2}x[12]\d{0,2}\.(jpg|jpeg|png)/i', // < 299x299
			'/\-[12]\d{0,2}x\d{0,2}\.(jpg|jpeg|png)/i', // < 299x99
			'/\-\d{0,2}x[12]\d{0,2}\.(jpg|jpeg|png)/i', // < 99x299
			'/\-\d{0,2}x\d{0,2}\.(jpg|jpeg|png)/i' // < 99x99
		);
		
		foreach($resolution_pregs as $resolution_preg){
			if(preg_match($resolution_preg, $src)){
				return true;
			}
		}

		return false;
	}

	static function is_third_party($src){
		global $speedycache;
		
		if(preg_match('/' . preg_quote($speedycache->lazy_load['host'], '/') . '/i', $src)){
			return false;
		}

		return true;
	}

	static function is_full($img){
		
		// to check homepage. sometimes is_home() does not work
		if(isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) < 2){
			return false;
		}

		if(is_home() || is_archive()){
			return false;
		}

		if(preg_match("/-\d+x\d+\.(jpg|jpeg|png)/i", $img)){
			if(preg_match("/\sclass\=[\"\'][^\"\']*size-medium[^\"\']*[\"\']/", $img)){
				return false;
			}
		}

		return true;
	}

	static function is_exclude($source){
		global $speedycache;
		
		/*
			to disable lazy load for rav-slider images
			<img data-bgposition="center center" data-bgparallax="8" data-bgfit="cover" data-bgrepeat="no-repeat"class="rev-slidebg" data-no-retina>
			<img width="1920" height="600" data-parallax="8" class="rev-slidebg" data-no-retina>
		*/
		if(preg_match('/class\="rev-slidebg"/i', $source) && preg_match("/data-(bg)*parallax\=/i", $source)){
			return true;
		}

		/*
			to exclude img tag which exists in json
			var xxx = {"content":"<a href=\"https:\/\/www.abc.com\"><img src='https:\/\/www.abc.com\/img.gif' \/><\/a>"}
		*/
		if(preg_match("/\\\\\//", $source)){
			return true;
		}

		/*
			<img src="my-image.jpg" data-no-lazy="1" alt="" width="100" width="100" />
			<img src="my-image.jpg" data-skip-lazy="1" alt="" width="100" width="100" />
		*/
		if(preg_match("/data-(no|skip)-lazy\s*\=\s*[\"\']\s*1\s*[\"\']/i", $source)){
			return true;
		}

		//Slider Revolution
		//<img src="dummy.png" data-lazyload="transparent.png" data-bgposition="center center" data-bgfit="cover" data-bgrepeat="no-repeat" data-bgparallax="off" class="rev-slidebg" data-no-retina>
		if(preg_match("/\sdata-lazyload\=[\"\']/i", $source)){
			return true;
		}

		//<img src="dummy.png" data-lazy-src="transparent.png">
		//<img src="dummy.png" data-gg-lazy-src="transparent.png">
		if(preg_match("/\sdata-([a-z-]*)lazy-src\=[\"\']/i", $source)){
			return true;
		}

		//<div style="background-image:url(&#039;https://www.g.com/site-data/plugins/bold-page-builder/img/image-palceholder.png&#039;);background-position:top;background-size:cover;" data-background_image_src="https://www.g.com/site-data/1.jpg">
		if(preg_match("/\sdata-background_image_src\=[\"\']/i", $source)){
			return true;
		}

		/*
		Smash Balloon Social Photo Feed
		<img src="https://site.com/site-data/plugins/instagram-feed/img/placeholder.png"
		*/
		if(preg_match('/instagram-feed\/img\/placeholder\.png/i', $source)){
			return true;
		}

		// don't to the replacement if the image is a data-uri
		if(preg_match("/src\=[\'\"]data\:image/i", $source)){
			return true;
		}


		foreach((array)$speedycache->lazy_load['exclude'] as $key => $value){
			if(preg_match('/' . preg_quote($value, '/') . '/i', $source)){
				return true;
			}
		}

		return false;
	}

	static function video($data, $inline_scripts){
		global $speedycache;
		
		if(isset($speedycache->settings['noscript'])){
			$inline_scripts = $inline_scripts . $speedycache->settings['noscript'];
		}

		$video_list = array();
		$video_start_index = false;

		for ($i = 0; $i < strlen($data); $i++){
			if(isset($data[$i - 5])){
				if(substr($data, $i - 5, 6) == '<video'){
					$video_start_index = $i - 5;
				}
			}

			if(isset($data[$i - 7])){
				if($video_start_index){
					if(substr($data, $i - 7, 8) == '</video>'){
						array_push($video_list, array('start' => $video_start_index, 'end' => $i));
						$video_start_index = false;
					}
				}
			}
		}

		if(!empty($video_list)){
			foreach(array_reverse($video_list) as $key => $value){
				$video_html = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));

				if(self::is_exclude($video_html)){
					continue;
				}

				$video_html = '<noscript data-type="speedycache">' . $video_html . '</noscript>';

				$data = substr_replace($data, $video_html, $value['start'], ($value['end'] - $value['start'] + 1));
			}
		}

		return $data;
	}

	static function background($content, $inline_scripts){
		global $speedycache;

		if(isset($speedycache->settings['noscript'])){
			$inline_scripts = $inline_scripts . $speedycache->settings['noscript'];
		}

		preg_match_all('/<(div|span|a|section|li)\s[^\>]*style\s*\=\s*[\"\'][^\"\']*(background\-image\s*\:\s*url\s*\(([^\)\(]+)\)\;?)[^\"\']*[\"\'][^\>]*>/i', $content, $matches, PREG_SET_ORDER);

		if(count($matches) > 0){
			/*
				[0] = full
				[1] = tag
				[2] = backgound image
				[3] = url
			*/
			foreach($matches as $key => $div){
				// don't to the replacement if the image appear in js
				if(preg_match("/" . preg_quote($div[0], '/') . '/i', $inline_scripts)){
					continue;
				}

				if(self::is_exclude($div[0])){
					continue;
				}

				$tmp = $div[0];
				//to remove backgound attribute
				$tmp = str_replace($div[2], '', $tmp);
				//to add lazy load attribute
				$div[3] = preg_replace("/[\"\']/", '', $div[3]);
				$tmp = preg_replace("/<([a-z]{1,4})\s/i", "<$1 data-speedycache-original-src='" . $div[3] . "' ", $tmp);

				$content = str_replace($div[0], $tmp, $content);
			}
		}

		return $content;
	}

	static function images($content, $inline_scripts){
		global $speedycache;

		if(isset($speedycache->settings['noscript'])){
			$inline_scripts = $inline_scripts . $speedycache->settings['noscript'];
		}

		$offset = 2;

		preg_match_all('/<img[^\>]+>/i', $content, $matches);
		$count = 0;
		if(count($matches[0]) > 0){
			foreach($matches[0] as $key => $img){
				$count++;
				$tmp_img = false;
				
				if(preg_match("/onload=[\"\']/i", $img)){
					continue;
				}

				if(preg_match("/src\s*\=\s*[\'\"]\s*[\'\"]/i", $img)){
					continue;
				}
				
				// Excluding images from lazy loading and adding fetchpriority to high,
				// as we need to load that image faster if we are not lazy loading them
				if(!empty($speedycache->options['exclude_above_fold']) && is_numeric($speedycache->options['exclude_above_fold'])){
					if($count < $speedycache->options['exclude_above_fold']){
						$tmp_img = $img;
						$img = preg_replace('/fetchpriority=["\'].*["\']/Us', '', $img);
						$img = preg_replace('/loading=["\'].*["\']/Us', '', $img);
						$img = preg_replace('/decoding=["\'].*["\']/Us', '', $img);
						
						$img = str_replace('<img', '<img fetchpriority="high" loading="eager" decoding="async" ', $img);
						$content = str_replace($tmp_img, $img, $content);

						continue;
					}
				}

				$should_continue = self::replace_img($inline_scripts, $img, $content);
				
				if(!empty($should_continue)){
					continue;
				}

				$tmp_img = preg_replace("/\sspeedycache-lazyload-disable\=[\"\']true[\"\']\s*/", " ", $img);
				$content = str_replace($img, $tmp_img, $content);
			}
		}

		return $content;
	}

	static function inject_data_src($img){
		global $speedycache;
		
		if(!preg_match("/\ssrc\s*\=[\"\'][^\"\']+[\"\']/i", $img)){
			return $img;
		}
		
		if(preg_match("/mc\.yandex\.ru\/watch/i", $img)){
			return $img;
		}

		$tmp_img = $img;
		$tmp_img = preg_replace('/\ssrc\s*\=/i', ' data-speedycache-original-src=', $tmp_img);
		$tmp_img = preg_replace('/\ssrcset\s*\=/i', ' data-speedycache-original-srcset=', $tmp_img);
		$tmp_img = preg_replace('/\ssizes\s*\=/i', ' data-speedycache-original-sizes=', $tmp_img);
		$tmp_img = preg_replace('/<img\s/i', '<img onload="speedycachell.r(this,true);" src="' . $speedycache->lazy_load['placeholder'] . "$2\" ", $tmp_img);

		// to add alt attribute for seo
		$tmp_img = preg_replace('/\salt\s*\=\s*[\"|\']\s*[\"|\']/', ' alt="blank"', $tmp_img);
		if(!preg_match('/\salt\s*\=\s*/i', $tmp_img)){
			$tmp_img = preg_replace('/<img\s+/i', '<img alt="blank" ', $tmp_img);
		}

		return $tmp_img;
	}

	static function replace_img($inline_scripts, $img, &$content){
		
		$tmp_img = false;
		
		// Don't do the replacement if the image appear in js
		if(preg_match('/' . preg_quote($img, '/') . '/i', $inline_scripts)){
			return false;
		}

		// Don't do the replacement if quote of src does not exist
		if(!preg_match("/\ssrc\s*\=[\"\']/i", $img) && preg_match("/<img/i", $img)){
			return true;
		}

		if(self::is_exclude($img)){
			$tmp_img = preg_replace('/\sspeedycache-lazyload-disable\=[\"\']true[\"\']\s*/', ' ', $img);
		} else if(preg_match('/speedycache-lazyload-disable/', $img)){
			$tmp_img = preg_replace('/\sspeedycache-lazyload-disable\=[\"\']true[\"\']\s*/', ' ', $img);
		} else {
			$tmp_img = self::inject_data_src($img);
		}
		
		if($tmp_img){
			$content = str_replace($img, $tmp_img, $content);
		}
		
		return true;
	}

	static function iframe($content, $inline_scripts){
		
		preg_match_all('/<iframe[^\>]+>/i', $content, $matches);

		if(count($matches[0]) > 0){
			foreach($matches[0] as $iframe){
				if(self::is_exclude($iframe)){
					continue;
				}

				self::iframe_replace($inline_scripts, $iframe, $content);
			}
		}

		return $content;
	}

	static function iframe_replace($inline_scripts, $iframe, &$content){
		// don't to the replacement if the frame appear in js
		if(preg_match('/' . preg_quote($iframe, "/") . '/i', $inline_scripts)){
			return;
		}

		if(preg_match("/onload=[\"\']/i", $iframe)){
			return;
		}

		if(preg_match("/(youtube|youtube-nocookie)\.com\/embed/i", $iframe) && !preg_match("/videoseries\?list/i", $iframe)){
			// to exclude /videoseries?list= because of problem with getting thumbnail
			$tmp_iframe = preg_replace("/\ssrc\=[\"\'](https?\:)?\/\/(www\.)?(youtube|youtube-nocookie)\.com\/embed\/([^\"\']+)[\"\']/i", " onload=\"speedycachell.r(this,true);\" data-speedycache-original-src=\"" . SPEEDYCACHE_WP_CONTENT_URL . "/plugins/".SPEEDYCACHE_PRO_BASE_NAME."/main/youtube.html#$4\"", $iframe);
		} else {
			$tmp_iframe = preg_replace("/\ssrc\=/i", " onload=\"speedycachell.r(this,true);\" data-speedycache-original-src=", $iframe);
		}

		$content = str_replace($iframe, $tmp_iframe, $content);

	}

	static function get_js_source(){
		
		$js = "\n<script data-speedycache-render=\"false\">" . file_get_contents(SPEEDYCACHE_PRO_DIR . '/assets/js/lazy-load.js') . "</script>\n";

		/**
		  * Structure of this array is
		  * searchable => replacer
		*/
		$js_replaces = array(
			'/\/\*[^\n]+\*\//' =>  '', //to remove comments
			'/var\sself/' => 'var s',
			'/self\./' => 's.',
			'/speedycache_lazy_load/' => 'speedycachell',
			'/(\.?)init(\:?)/' => '$1i$2',
			'/(\.?)load_images(\:?)/' => '$1li$2',
			'/\s*(\+|\=|\:|\;|\{|\}|\,)\s*/' => '$1',
			'/originalsrcset/' => 'ot',
			'/originalsrc/' => 'oc',
			'/load_sources/' => 'ls',
			'/set_source/' => 'ss',
			'/sources/' => 's',
			'/winH/' => 'w',
			'/number/' => 'n',
			'/elemRect/' => 'er',
			'/parentRect/' => 'pr',
			'/parentOfE/' => 'p',
			'/top(\=|\+)/' => 't$1'
		);
		
		foreach($js_replaces as $search => $replace){
			$js = preg_replace($search, $replace, $js);
		}

		return $js;
	}

}

