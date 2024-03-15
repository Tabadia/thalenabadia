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

class Enhanced{

	static function init(){
		global $speedycache;
		
		$speedycache->enhanced = array();
		$speedycache->enhanced['html'] = '';
		$speedycache->enhanced['head_html'] = '';
		$speedycache->enhanced['body_html'] = '';
		$speedycache->enhanced['inline_scripts'] = '';
		$speedycache->enhanced['cache_speedycache_minified'] = '';
		$speedycache->enhanced['cache_speedycache_minified'] = 'cache/speedycache/'.SPEEDYCACHE_SERVER_HOST. '/assets';
	}
	
	// Removes white space after </html> & \r & any white space 
	static function remove_trailing_html_space($content){
		global $speedycache;
		
		$content = preg_replace("/<\/html>\s+/", '</html>', $content);
		$content = str_replace("\r", '', $content);
		return preg_replace("/^\s+/m", '', ((string) $content));
	}

	static function remove_head_comments(){
		global $speedycache;
		
		$data = $speedycache->enhanced['head_html'];
		$comment_list = array();
		$comment_start_index = false;

		for($i = 0; $i < strlen( $data ); $i++){
			if(isset($data[$i-3])){
				if($data[$i-3].$data[$i-2].$data[$i-1].$data[$i] == '<!--'){
					if(!preg_match("/if\s+|endif\s*\]/", substr($data, $i, 17))){
						$comment_start_index = $i-3;
					}
				}
			}

			if(isset($data[$i-2])){
				if($comment_start_index){
					if($data[$i-2].$data[$i-1].$data[$i] == '-->'){
						array_push($comment_list, array('start' => $comment_start_index, 'end' => $i));
						$comment_start_index = false;
					}
				}
			}
		}

		if(!empty($comment_list)){
			foreach(array_reverse($comment_list) as $key => $value){
				$data = substr_replace($data, '', $value['start'], ($value['end'] - $value['start'] + 1));
			}

			$speedycache->enhanced['html'] = str_replace($speedycache->enhanced['head_html'], $data, $speedycache->enhanced['html']);
		}

		return $speedycache->enhanced['html'];
	}

	static function eliminate_newline($start_string, $end_string, $tmp_html){
		$data = $tmp_html;

		$list = array();
		$start_index = false;
		$end_index = false;

		for($i = 0; $i < strlen( $data ); $i++){
			if(substr($data, $i, strlen($start_string)) == $start_string){
				if(!$end_index){
					$start_index = $i;
				}
			}

			if($start_index && $i > $start_index){
				if(substr($data, $i, strlen($end_string)) == $end_string){
					$end_index = $i + strlen($end_string) - 1;
					$text = substr($data, $start_index, ($end_index - $start_index + 1));
					
					array_push($list, array('start' => $start_index, 'end' => $end_index, 'text' => $text));

					$start_index = false;
					$end_index = false;
				}
			}
		}

		if(isset($list[0])){
			$list = array_reverse($list);

			foreach($list as $key => $value){
				if(preg_match("/(<script|<style|<textarea)/i", $value['text'])){
					continue;
				}

				//var $bodybg=$('<div id="ncf-body-bg"/>').prependTo($body);
				if(preg_match("/\)\.prependTo\(/i", $value['text'])){
					continue;
				}

				//<div class="wp_syntax" style="position:relative;">
				if(preg_match("/<div\s+class\=\"wp\_syntax\"[^\>]*>/i", $value['text'])){
					continue;
				}

				$value['text'] = preg_replace("/\s+/", " ", ((string)$value['text']));

				$tmp_html = substr_replace($tmp_html, $value['text'], $value['start'], ($value['end'] - $value['start'] + 1));
			}
		}

		return $tmp_html;
	}

	static function minify_inline_css($data){
		global $speedycache;
		
		$style_list = array();
		$style_start_index = false;

		for($i = 0; $i < strlen( $data ); $i++){
			if(isset($data[$i-5])){
				if(substr($data, $i - 5, 6) == '<style'){
					$style_start_index = $i - 5;
				}
			}

			if(isset($data[$i-7])){
				if($style_start_index){
					if(substr($data, $i - 7, 8) == '</style>'){
						array_push($style_list, array('start' => $style_start_index, 'end' => $i));
						$style_start_index = false;
					}
				}
			}
		}

		if(!empty($style_list)){
			foreach(array_reverse($style_list) as $key => $value){
				// document.write('<style type="text/css">div{}</style')
				$prev_20_chars = substr($data, $value['start'] - 20, 20);
				
				if(strpos($prev_20_chars, 'document.write') !== false){
					continue;
				}

				$inline_style = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));
				
				if(strlen($inline_style) > 15000){
					$part_of_inline_style = substr($inline_style, 0, 15000);
				}else{
					$part_of_inline_style = $inline_style;
				}

				if(preg_match('/'.preg_quote($part_of_inline_style, '/').'/i', $speedycache->enhanced['inline_scripts'])){
					continue;
				}

				if(preg_match("/<style\s+(amp-boilerplate|amp-custom)>/", $inline_style)){
					continue;	
				}

				$inline_style = \SpeedyCache\Enhanced::minify_css($inline_style);

				$inline_style = preg_replace("/\/\*(.*?)\*\//s", "\n", $inline_style); //replaces comments with \n
				$inline_style = preg_replace("/(<style[^\>]*>)\s+/i", "$1", $inline_style); //removes white space after <style> tag
				$inline_style = preg_replace("/\s+(<\/style[^\>]*>)/i", "$1", $inline_style); //removes white space before </style> tag

				$inline_style = str_replace(' type="text/css"', '', $inline_style);
				$inline_style = str_replace(' type="text/css"', '', $inline_style);

				$data = substr_replace($data, $inline_style, $value['start'], ($value['end'] - $value['start'] + 1));
			}
		}

		return $data;
	}

	static function remove_html_comments($data){
		$comment_list = array();
		$comment_start_index = false;

		for($i = 0; $i < strlen($data); $i++){
			if(isset($data[$i-3])){
				if($data[$i-3].$data[$i-2].$data[$i-1].$data[$i] == "<!--"){
					if(!preg_match("/if\s+|endif\s*\]/", substr($data, $i, 17))){
						$comment_start_index = $i-3;
					}
				}
			}

			if(isset($data[$i-2])){
				if($comment_start_index){
					if($data[$i-2].$data[$i-1].$data[$i] == '-->'){
						array_push($comment_list, array('start' => $comment_start_index, 'end' => $i));
						$comment_start_index = false;
					}
				}
			}
		}

		if(!empty($comment_list)){
			foreach(array_reverse($comment_list) as $key => $value){
				if(($value['end'] - $value['start']) > 4){
					$comment_html = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));

					if(preg_match("/google\_ad\_slot/i", $comment_html)){
					}else{
						$data = substr_replace($data, '', $value['start'], ($value['end'] - $value['start'] + 1));
					}
				}
			}
		}

		return $data;
	}

	static function minify_html(){
		global $speedycache;
		
		$tmp_html = $speedycache->enhanced['html'];

		$tmp_html = self::remove_trailing_html_space($tmp_html);
		$tmp_html = self::eliminate_newline('<div', '</div>', $tmp_html);
		$tmp_html = self::eliminate_newline('<li', '</li>', $tmp_html);

		$tmp_html = self::minify_inline_js($tmp_html);
		$tmp_html = self::minify_inline_css($tmp_html);

		$tmp_html = self::remove_html_comments($tmp_html);

		$tag_list = 'p|div|span|img|nav|ul|li|header|a|b|i|article|section|footer|style|script|link|meta|body';

		$tmp_html = preg_replace_callback("/\<(".$tag_list.")\s+[^\>\<]+\>/i", '\SpeedyCache\Enhanced::remove_spaces_in_tag', $tmp_html);
		$tmp_html = preg_replace('/\h+<\//', '</', $tmp_html);
		
		// BECAUSE of JsemÂ<span class="label">
		// - need to remove spaces between >  <
		// - need to remove spaces between <span>  Assdfdf </span>
		// $tmp_html = preg_replace("/\h*\<(".$tag_list.")\s+([^\>]+)>\h*/i", "<$1 $2>", $tmp_html);
		// $tmp_html = preg_replace("/\h*\<\/(".$tag_list.")>\h*/i", "</$1>", $tmp_html);
		$tmp_html = preg_replace("/\s*<\/div>\s*/is", "</div>", $tmp_html);

		$speedycache->enhanced['html'] = $tmp_html;

		return $speedycache->enhanced['html'];
	}

	static function search_in_inline_scripts($content){
		global $speedycache;
		
		if(strpos($speedycache->enhanced['inline_scripts'], $content) === false){
			return false;
		}
		
		return true;
	}

	static function remove_spaces_in_tag($matches){
		if(self::search_in_inline_scripts($matches[0])){
			return $matches[0];
		}
		
		/**
		  * Structure of this array is
		  * searchable => replacer
		*/	
		$pregs_replaces = array(
			'/([\"\'])\s+\/>/' => '$1/>', //  <img id="1"  />
			'/\s+/' => ' ', // <div      id="1">
			'/\s+([\"\'])/' => '$1', // <div id="1  ">
			'/([a-z])\=([\"\'])\s+/' => '$1=$2', // <div id="  1"> <img src="data:image/gif;base64,R0lAICRAEAOw==" lazy="image.jpg" />
			'/\h*class\=\'\'\h*/' => ' ', // <ul class="">
			'/\h*class\=\"\"\h*/' => ' ', // <ul class=''>
		);
		
		foreach($pregs_replaces as $searchable => $replacer){
			$matches[0] = preg_replace($searchable, $replacer, $matches[0]);
		}

		// <div style="">
		if(!preg_match("/id\=\"ctf_/", $matches[0])){
			/* 
			to exclude for Custom Twitter Feeds Pro Personal
			<div class="ctf-item ctf-author-msdsmarine ctf-new ctf-hide-avatar ctf-retweet ctf-tc-checked" id="ctf_1323705595325800448" style="">
			*/
			$matches[0] = preg_replace("/\h*style\=[\"\'][\"\']\h*/", " ", $matches[0]);
		}

		// <div id="1"  >
		// <div  >
		$matches[0] = preg_replace("/\h+\>/", ">", $matches[0]);

		// <script src='//bqcmw.js' type="text/javascript"></script>
		$matches[0] = self::remove_type_attribute_for_js($matches[0]);

		return $matches[0];
	}

	static function remove_type_attribute_for_js($script){
		if(preg_match("/src\s*\=\s*[\"\']/", $script)){
			$script = preg_replace("/\stype\s*\=\s*[\'\"][^\"\']+[\'\"]/", " ", $script);
			$script = preg_replace("/\s+/", " ", $script);
			$script = preg_replace("/([\'\"])\s>/", "$1>", $script);
		}

		return $script;
	}

	static function remove_single_line_comments($html){
		$html = preg_replace("/<!--((?:(?!-->).)+)-->/", '', $html);
		$html = preg_replace("/\/\*((?:(?!\*\/).)+)\*\//", '', $html);
		return $html;
	}

	/* CSS Part Start */
	static function minify_css($source){
		$data = $source;
		$curl_list = array();
		$curl_start_index = false;

		$curl_start_count = 0;
		$curl_end_count = 0;

		for($i = 0; $i < strlen( $data ); $i++){
			if($data[$i] == '{'){
				$curl_start_count++;
				if(!$curl_start_index){
					$curl_start_index = $i;
				}
			}

			if($data[$i] == '}'){
				// .icon-basic-printer:before{content:"}";}
				if(isset($data[$i+1]) && $data[$i+1] != "'" && $data[$i+1] != "'"){
					$curl_end_count++;
				}
			}

			if($curl_start_count && $curl_start_count == $curl_end_count){
				array_push($curl_list, array('start' => $curl_start_index - 3, 'end' => $i + 3));

				$curl_start_count = 0;
				$curl_end_count = 0;
				$curl_start_index = false;
			}
		}

		if(!empty($curl_list)){
			foreach(array_reverse($curl_list) as $key => $value){
				$new_data = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));

				if(!preg_match("/[^\{]+\{[^\{]+\{/", $new_data)){
					$new_data = preg_replace("/\s+/", " ", ((string) $new_data));
					$new_data = preg_replace("/\s+}/", "}", $new_data); //removes white space before "}"
					$new_data = preg_replace("/}\s+/", "} ", $new_data); //removes white space after "}"
					$new_data = preg_replace("/\s*(\{|\;|\:)\s*/", "$1", $new_data);

					$data = substr_replace($data, $new_data, $value['start'], ($value['end'] - $value['start'] + 1));

				}else{
					$first = strpos($new_data, '{');
					$last = strrpos($new_data, '}');
					$new_data_tmp = substr($new_data, $first+1, $last-$first-1);
					$new_data_tmp = \SpeedyCache\Enhanced::minify_css($new_data_tmp);

					$new_data = substr_replace($new_data, $new_data_tmp, $first+1, ($last-$first-1));

					$data = substr_replace($data, $new_data, $value['start'], ($value['end'] - $value['start'] + 1));
				}
			}

			$source = $data;
		}

		//@media (max-width: 767px){
		$source = preg_replace("/\@media\s*\(\s*(max-width|min-width)\s*\:\s*([^\(\)\{\}\s]+)\s*\)\s*\{/", "@media($1:$2){", $source);
		//@media (min-width: 768px) and (max-width: 1018px){
		$source = preg_replace("/\@media\s*\(\s*(max-width|min-width)\s*\:\s*([^\(\)\{\}\s]+)\s*\)\s*and\s*\(\s*(max-width|min-width)\s*\:\s*([^\(\)\{\}\s]+)\s*\)\s*\{/", "@media($1:$2) and ($3:$4){", $source);
		//@media screen and (max-width: 479px){
		$source = preg_replace("/\@media\s+screen\s+and\s*\(\s*(max-width|min-width)\s*\:\s*([^\(\)\{\}\s]+)\s*\)\s*\{/", "@media screen and ($1:$2){", $source);

		/*
		article,
		h2,
		div:first-child,
		.main{padding:0;}
		*/
		$source = preg_replace("/^([a-z0-9\_\.\-\:\>\s]+\,)\s+/im", "$1 ", $source);

		return $source;

		//$source = preg_replace_callback("/\s*\{((?:(?!content|\}).)+)\}\s*/", '\SpeedyCache\Enhanced::eliminate_newline_for_css'), $source);
		//return $source;
	}

	// Regex to replace new line after \n /\s*\;(?:\s*|\n)/
	//Replaces Space before and after { } ; :
	static function eliminate_newline_for_css($matches){
		$matches[0] = preg_replace("/\s+/", " ", ((string) $matches[0]));
		$matches[0] = preg_replace("/\s*{\s*/", "{", $matches[0]);
		$matches[0] = preg_replace("/\s*}\s*/", "}", $matches[0]);
		$matches[0] = preg_replace("/\s*\;\s*/", ";", $matches[0]);
		$matches[0] = preg_replace("/\s*\:\s*/", ":", $matches[0]);

		return $matches[0]."\n";
	}

	static function render_blocking($html, $render_blocking_css = false){
		\SpeedyCache\RenderBlocking::init($html);
		return \SpeedyCache\RenderBlocking::action($render_blocking_css);
	}

	static function google_fonts(){
		//for checking
	}

	static function lazy_load($content){
		global $speedycache;

		\SpeedyCache\LazyLoad::init();
		
		$funcs = array(
			'\SpeedyCache\LazyLoad::images',
			'\SpeedyCache\LazyLoad::iframe',
			'\SpeedyCache\LazyLoad::background',
			'\SpeedyCache\LazyLoad::video'
		);
		
		foreach($funcs as $fn){
			// if(!function_exists($fn)){
				// continue;
			// }

			$fn_res = call_user_func_array($fn, array($content, $speedycache->enhanced['inline_scripts']));
			
			if(empty($fn_res)){
				continue;
			}
			
			$content = $fn_res;
		
		}
		
		return $content;
	}

	/* CSS Part Start */

	/* Js Part Start */
	// TODO:: not used anywhere
	static function single_line_js($source){
		$source = preg_replace("/\n/", '', $source);

		return $source;
	}

	static function minify_js($source, $inline_js = false){
		//$source = preg_replace("/\n\/\/.*/", "", $source);
		//$source = preg_replace("/\/\*.*?\*\//s", "", $source);

		if(preg_match("/dynamicgoogletags\.update\(\)/i", $source)){
			$source = "<script>dynamicgoogletags.update();</script>";
			
			return $source;
		}

		// <script>
		//   (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		//   (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		//   m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		//   })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		//   ga('create', 'UA-9999-9', 'auto');
		//   ga('send', 'pageview');
		// </script>
		if(preg_match("/<script[^\>]*>\s*\(function\(i,s,o,g,r,a,m\)\{i\[\'GoogleAnalyticsObject\'\]/i", $source)){
			if(preg_match("/ga\(\'send\',\s*\'pageview\'\)\;\s*<\/script>/i", $source)){
				$source = preg_replace("/\s+/", " ", ((string) $source));
				$source = preg_replace("/\s*<(\/?)script([^\>]*)>\s*/", "<$1script$2>", $source);

				return $source;
			}
		}

		// sometimes the lines are ended with "\r" instead of "\n"
		$source = str_replace("\r", "\n", $source);

		$source = preg_replace("/^\s+/m", '', $source);

		if(empty($inline_js)){
			// // --></script> in html
			//$source = preg_replace("/\n\/\/[^\n]+/", "", $source); // to remove single line comments
			$source = preg_replace_callback("/\n\/\/[^\n]+/", '\SpeedyCache\Enhanced::remove_single_line_comments_from_js', $source);
		}

		if(!empty($inline_js)){
			if(preg_match("/var\sptsTables/i", $source) && preg_match("/var\sptsBuildConst/i", $source)){
			}
			//to remove only CDATA from inline js
			$source = preg_replace("/\/\*\s*\<\!\[CDATA\[\s*\*\//", "", $source);
			$source = preg_replace("/\/\*\s*\]\]\>\s*\*\//", "", $source);
		}

		//<script>//alert();</script>
		if(preg_match("/<script[^\>]*>\s*\/\/[^\n]*<\/script>/i", $source)){
			return '';
		}
		
		$source = preg_replace_callback("/([a-z]{4,5}\:)?\/\/[^\n]*/", '\SpeedyCache\Enhanced::remove_single_line_comments_from_js', $source);

		$source = preg_replace("/\}\)\;[^\S\r\n]+/", "});", $source);

		$source = preg_replace("/^\s+/m", "", $source);

		
		$source = preg_replace("/\s*(\!|\=)(\={1,3})\s*/", "$1$2", $source);

		// to remove spaces at the end of the line
		$source = preg_replace("/(\D)[^\S\r\n]+\n/", "$1\n", $source);

		$source = preg_replace("/([^\[\.\?])[^\S\r\n]+\:[^\S\r\n]+([^\]\.\?])/", "$1:$2", $source);

		$source = preg_replace("/([^\s\|])[^\S\r\n]*\&\&[^\S\r\n]*([^\s\|])/", "$1&&$2", $source);
		$source = preg_replace("/([^\s\&])[^\S\r\n]*\|\|[^\S\r\n]*([^\s\&])/", "$1||$2", $source);
		// @media all and (width), maybe later we  can do preg_replace_callback()
		//b.match(/^(<div><br( ?\/), no need to remove the spage between ( and ?
		//dashArray.replace(/( *, *)/g, no need to remove the spage between ( and *
		$source = preg_replace("/[^\S\r\n]*\([^\S\r\n]+([^\?\*\+])/", "($1", $source);
		$source = preg_replace("/and\(/", "and (", $source);
		//------
		$source = preg_replace("/([^\s\=\!])[^\S\r\n]*\=[^\S\r\n]*([^\s\=\!])/", "$1=$2", $source);

		$source = preg_replace("/\)\s+\{/", "){", $source);
		$source = preg_replace("/\}\s+}/s", "}}", $source);
		$source = preg_replace("/\};\s+}/s", "};}", $source);
		$source = preg_replace("/\}\s*else\s*\{/", "}else{", $source);
		$source = preg_replace("/\}[^\S\r\n]*else[^\S\r\n]*if[^\S\r\n]*\(/", "}else if(", $source);
		$source = preg_replace("/if\s*\(\s*/", "if(", $source);
		$source = preg_replace("/[^\S\r\n]+\)/", ")", $source);

		$source = preg_replace("/<script([^\>\<]*)>\s*/i", "<script$1>", $source);
		$source = preg_replace("/\s*<\/script>/i", "</script>", $source);

		// .name( something)
		$source = preg_replace("/(\.[A-Za-z\_]+\()\s{1,2}/", "$1", $source);

		// Muli-Line Comments Start
		$source = preg_replace_callback("/\/\*(.*?)\*\//s", '\SpeedyCache\Enhanced::remove_multi_line_comments_from_js', $source);
		// END

		$source = str_replace("\xEF\xBB\xBF", '', $source);

		$source = preg_replace("/^\s+/m", '', $source);

		//<script><!--
		//var x=5;
		//</script>
		if(!empty($inline_js)){
			if(preg_match("/<script[^\>]*><\!--/i", $source)){
				if(!preg_match("/-->/i", $source)){
					$source = preg_replace("/(<script[^\>]*>)<\!--\n/i", "$1", $source);
				}
			}
		}

		return $source;
	}

	static function minify_inline_js($data){
		global $speedycache;
		
		$script_list = array();
		$script_start_index = false;

		for($i = 0; $i < strlen( $data ); $i++){
			if(isset($data[$i - 6])){
				if(substr($data, $i - 6, 7) == '<script'){
					$script_start_index = $i - 6;
				}
			}

			if(isset($data[$i - 8])){
				if($script_start_index){
					if(substr($data, $i - 8, 9) == '</script>'){
						array_push($script_list, array('start' => $script_start_index, 'end' => $i));
						$script_start_index = false;
					}
				}
			}
		}

		if(!empty($script_list)){
			foreach(array_reverse($script_list) as $key => $value){
				$inline_script = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));
				
				if(preg_match("/google\_ad\_slot/i", $inline_script)){
					$speedycache->enhanced['inline_scripts'] = $speedycache->enhanced['inline_scripts'].$inline_script;
					continue;
				}

				if(preg_match("/<script[^\>]+src=[\'\"][^\>]+>/i", $inline_script)){
					continue;
				}

				if(preg_match("/<script[^\>]+text\/template[^\>]+>/i", $inline_script)){
					continue;
				}

				$speedycache->enhanced['inline_scripts'] = $speedycache->enhanced['inline_scripts'].$inline_script;
					
				$inline_script = \SpeedyCache\Enhanced::minify_js($inline_script, true);

				$inline_script = str_replace(' type="text/javascript"', '', $inline_script);
				$inline_script = str_replace(' type="text/javascript"', '', $inline_script);

				$speedycache->enhanced['inline_scripts'] = $speedycache->enhanced['inline_scripts'].$inline_script;

				$data = substr_replace($data, $inline_script, $value['start'], ($value['end'] - $value['start'] + 1));

			}
		}

		return $data;
	}

	static function remove_multi_line_comments_from_js($matches){

		//segs.unshift('//*[@id="' + elm.getAttribute('id') + '"]');
		if(preg_match("/\/\*\[\@/", $matches[0])){
			return $matches[0];
		}
		
		if(preg_match("/\/\*\@cc_on/i", $matches[0])){
			return $matches[0];
		}

		if(preg_match("/\.exec\(|\.test\(|\.match\(|\.search\(|\.replace\(|\.split/", $matches[0])){
			return $matches[0];
		}

		if(preg_match("/function\(/", $matches[0])){
			return $matches[0];
		}

		//c("unmatched `/*`");
		if(preg_match("/^\/\*\`\"\)\;/", $matches[0])){
			return $matches[0];
		}

		// <script type='text/javascript'>
		// /* <![CDATA[ */
		// var icegram_data = {"custom_js":"<script type=\"text\/javascript\">\/* add your js code here *\/ <\/script>"};
		// /* ]]> */
		// </script>
		if(preg_match("/\\/script>/", $matches[0]) && preg_match("/\*\\//", $matches[0])){
			return $matches[0];
		}

		// {comment:{pattern:/^([ \t]*)\/[\/*].*(?:(?:\r?\n|\r)\1[ \t]+.+)*/m,lookbehind:!0}}
		if(preg_match("/\.\+\)\*\//", $matches[0])){
			return $matches[0];
		}

		// var sourceURL = '\n/*\n//# sourceURL=' + (options.sourceURL || '/lodash/template/source[' + (templateCounter++) + ']') + '\n*/';
		if(preg_match("/\/\*\\\\n\/\/\#\s+sourceURL/i", $matches[0])){
			return $matches[0];
		}

		// function(e){return"/*# sourceURL=".concat(r.sourceRoot).concat(e," */")
		if(preg_match("/\/\*\#\s+sourceURL/i", $matches[0])){
			return $matches[0];
		}

		// /*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(o))))," */
		if(preg_match("/\/\*\#\s+sourceMappingURL/i", $matches[0])){
			return $matches[0];
		}

		// var COMMON_HEADERS = {'Accept': 'application/json, text/plain, */*'};
		if(preg_match("/\/\*\'\}\;/", $matches[0])){
			return $matches[0];
		}

		return '';
	}

	static function remove_single_line_comments_from_js($matches){
		if(preg_match("/\n\/\/[^\n]+/", $matches[0])){
			// // */
			if(preg_match("/\/\/\s*\*\//", $matches[0])){
				return $matches[0];
			}

			return '';
		}

		// // something */
		if(preg_match("/\/\/[^\n\t]*\*\//", $matches[0])){
			return $matches[0];
		}

		// var url = {"name" : "something",
		// 		   "url"  : '//$1/p/$2/media/?size=l'
		// 		  };
		if(preg_match("/\'\h*$/", $matches[0])){
			if(substr_count($matches[0], "'") == 1){
				return $matches[0];
			}
		}

		// ia=/^\.\//;x=Object.prototype;var K=x.toString,
		if(preg_match("/^\/\/\;/", $matches[0])){
			return $matches[0];
		}

		// var snd = new Audio("data:audio/wav;base64,//uQRAAAAWMSLwUIYAAsYkXgoQw3kuZGUAAAAAAAAAACU=");
		if(preg_match("/^\/\/[A-Za-z0-9\+\/\=]+[\'\"]\)\;/", $matches[0])){
			return $matches[0];
		}

		// "data:audio/wave;base64,/UklGRiYAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQIAAAD//w==":"about:blank",
		if(preg_match("/^\/\/w\=\=\"/", $matches[0])){
			return $matches[0];
		}


		// var div = {"background-image":"url(data:image/png;base64,wD/AP+gvatMW2UYx//POaRK5CYII=)"};
		if(preg_match("/^\/\/[A-Za-z0-9\+\/\=]+\)[\'\"]\}/", $matches[0])){
			return $matches[0];
		}

		// base64
		if(preg_match("/^\/\/[A-Za-z0-9\+\/\=]{150}/", $matches[0])){
			return $matches[0];
		}

		// var a = '<a href="javascript://" id="nextLink" title="' + opts.strings.nextLinkTitle + '"></a>';
		if(preg_match("/^cript\:\/\/\"/", $matches[0])){
			return $matches[0];
		}

		// url.replace( /^http:\/\//i, 'https://' );
		//domain = domain.replace(new RegExp(/^http\:\/\/|^https\:\/\/|^ftp\:\/\//i),"");
		if(preg_match("/^\/\/i(\,|\))/", $matches[0])){
			return $matches[0];
		}

		// {pattern:/\/\*[\*!][\s\S]*?\*\//gm,alias:"co2"}
		// d=b?/[&<>"'\/]/g:/&(?!#?\w+;)|<|>|"|'|\//g;
		// replace(/\//g,"")
		// e.match(/^https?:\/\//g)
		if(preg_match("/^\/\/gm?(\,|\)|\;)/", $matches[0])){
			return $matches[0];
		}

		// match(/^https?:\/\//)
		// var pattern = RegExp("^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\\?([^#]*))?(#(.*))?");
		if(preg_match("/^\/\/(\)|\()/", $matches[0])){
			return $matches[0];
		}

		//src="//about:blank" frameborder="0" allowfullscreen></iframe>'+
		if(preg_match("/^\/\/about\:blank/", $matches[0])){
			return $matches[0];
		}

		//"<img src='http"+(location.href.indexOf('https:')==0?'s://www':'://track')+".websiteceo.com/m/?"+q+"' width='1' height='1' border='0' align='left'>";
		if(preg_match("/^\/\/www\'/", $matches[0])){
			return $matches[0];
		}

		// if(URL.match( /^https?:\/\// ) ){
		if(preg_match("/^\/\/\s*\)\s*\)\s*\{/", $matches[0])){
			return $matches[0];
		}

		// "string".replace(/\//,3);
		if(preg_match("/^\/\/\s*\,/", $matches[0])){
			return $matches[0];
		}

		// src = src.replace('https?://[^./].','');
		if(preg_match("/^\/\/\[[^\]\[]+\]/", $matches[0])){
			return $matches[0];
		}

		// comments: /\/\*[^*]*\*+([^/][^*]*\*+)*\//gi,
		if(preg_match("/^\/\/\s*gi\s*\,/", $matches[0])){
			return $matches[0];
		}

		// var proto = document.location.protocol, host = "whatshelp.io", url = proto + "//static." + host;
		if(preg_match("/^\/\/static\./i", $matches[0])){
			return $matches[0];
		}

		// whatsapp://send?text=
		// NOTE: preg_match_replace gets only 5 chars so we check "tsapp://" instead of "whatsapp://"
		if(preg_match("/^tsapp\:\/\/send/", $matches[0])){
			return $matches[0];
		}

		// sms://?&body="+postTitle+" "+postUrl
		if(preg_match("/^\/\/\?\&/", $matches[0])){
			return $matches[0];
		}

		// viber://forward?text="+postTitle+" "+postUrl
		if(preg_match("/^viber\:\/\//", $matches[0])){
			return $matches[0];
		}

		//threema://compose?text="+postTitle+" "+postUrl
		if(preg_match("/^reema\:\/\//", $matches[0])){
			return $matches[0];
		}

		// weixin://
		if(preg_match("/^eixin\:\/\//", $matches[0])){
			return $matches[0];
		}

		// fb-messenger://share?
		if(preg_match("/^enger\:\/\//", $matches[0])){
			return $matches[0];
		}

		// rtmp://37.77.2.234:1935/redirect/live.flv
		if(preg_match("/^rtmp\:\/\//", $matches[0])){
			return $matches[0];
		}

		// comgooglemaps://?q=40.956572,29.0859053&directionsmode=driving
		if(preg_match("/^emaps\:\/\//", $matches[0])){
			return $matches[0];
		}

		// javascript://
		if(preg_match("/^cript\:\/\//", $matches[0])){
			return $matches[0];
		}

		// jsFileLocation:"//29.59.155.173/~cfo/site-data/plugins/revslider/public/assets/js/",
		if(preg_match("/^\/\/([0-9]{1,3}\.){3}[0-9]{1,3}\/\~/", $matches[0])){
			return $matches[0];
		}

		// var url = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
		if(preg_match("/\/\/\=\]/", $matches[0])){
			return $matches[0];
		}

		if(preg_match("/^maps\:\/\//", $matches[0])){
			return $matches[0];
		}

		// "line://msg/text/" + postTitle + "! " + postUrl
		if(preg_match("/^line\:\/\//", $matches[0])){
			return $matches[0];
		}

		// document.write('<script defer src="//:"></script>');
		if(preg_match("/^\/\/\:\"/", $matches[0])){
			return $matches[0];
		}

		// url: "//$1/p/$2/media/?size=l"
		if(preg_match("/^\/\/\\$/", $matches[0])){
			return $matches[0];
		}
		
		if(preg_match("/^\/\/\//", $matches[0])){
			return $matches[0];
		}
		
		if(preg_match("/^http/", $matches[0])){
			return $matches[0];
		}

		// var xxx={"case":"\nhttp://www.google.com"};
		if(preg_match("/^nhttp/", $matches[0])){
			return $matches[0];
		}

		// var currUrl = 'file://' + "something";
		if(preg_match("/^file\:\/\//i", $matches[0])){
			return $matches[0];
		}

		//<a href="javascript://nop/" class="morelink">
		if(preg_match("/cript\:\/\/nop/i", $matches[0])){
			return $matches[0];
		}

		// Flash.RTMP_RE = /^rtmp[set]?:\/\//i;
		if(preg_match("/^\/\/i\;/", $matches[0])){
			return $matches[0];
		}

		//segs.unshift('//*[@id="' + elm.getAttribute('id') + '"]');
		if(preg_match("/^\/\/\*\[/", $matches[0])){
			return $matches[0];
		}

		// e.write('<!DOCTYPE html "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">')
		if(preg_match("/^\/\/W3C\/\/DTD\s+XHTML/i", $matches[0])){
			return $matches[0];
		}

		// var sourceURL = '\n/*\n//# sourceURL=' + (options.sourceURL || '/lodash/template/source[' + (templateCounter++) + ']') + '\n*/';
		// var xxx = "} catch (e){ throw 'TemplateError: ' + e + ' (on " + name + "' + ' line ' + this.line + ')'; } " + "//@ sourceURL=" + name + "\n" // source map
		if(preg_match("/^\/\/(\#|\@)\s+sourceURL/i", $matches[0])){
			return $matches[0];
		}

		// options.tileLayerThem = '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
		if(preg_match("/^\/\/\{[^\}]+\}\./", $matches[0])){
			return $matches[0];
		}

		// document.write("<"+"script defer src=\"//:\" id=\"__onload_ie_pixastic__\"></"+"script>");
		if(preg_match("/^\/\/\:\\\\(\"|\')/", $matches[0])){
			return $matches[0];
		}

		// a.src='//cdn.'+w[r+'h']+'/libs/b.js';
		if(preg_match("/^\/\/cdn\./", $matches[0])){
			return $matches[0];
		}

		//<!DOCTYPE svg "-//W3C//DTD SVG 1.1//EN
		if(preg_match("/^\/\/W3C/i", $matches[0])){
			return $matches[0];
		}

		/*
		//# sourceMappingURL=angular.min.js.map
		//# sourceMappingURL=data:application
		*/
		if(preg_match("/sourceMappingURL\s*\=\s*(angular\.min\.js\.map|data\:application)/i", $matches[0])){
			return $matches[0];
		}

		if(preg_match("/\.exec\(|\.test\(|\.match\(|\.search\(|\.replace\(|\.split/", $matches[0])){
			return $matches[0];
		}

		if(preg_match("/^\/\/(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}/", $matches[0])){
			return $matches[0];
		}

		if(preg_match("/\'|\"/", $matches[0])){
			// ' something
			if(preg_match("/^\/\/\s*[\'|\"]/", $matches[0])){
				return $matches[0];
			}

			// new Validator.Assert().Regexp('(https?:\\/\\/)?(www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{2,256}\\.[a-z]{2,4}\\b([-a-zA-Z0-9@:%_\\+.~#?&//=]*)', 'i');
			if(preg_match("/[\'\"]\,\s*[\'\"]i[\'\"]\)\;/", $matches[0])){
				return $matches[0];
			}

			/*static function speedycache_powerful_html_Uc(a,b){var c=Q&&Q.isAvailable(),d=c&&!(nb.kd||!0===nb.get("previous_websocket_failure"));b.ce&&(c||L("
			wss:// URL used, but browser isn't known to support websockets.  Trying anyway."),d=!0);if(d)a.Mb=[Q];else{var e=a.Mb=[];Vb(Vc,function(a,b){b&&b.isAvailable()&&e.push(b)})}}static function speedycache_powerful_html_Wc(a){if(0<a.Mb.length)return a.Mb[0];throw Error("No transports available");};static function speedycache_powerful_html_Xc(a,b,c,d,e,f){this.id=a;this.e=Mb("c:"+this.id+":");this.Lc=c;this.Ab=d;this.S=e;this.Kc=f;this.M=b;this.fc=[];this.Zc=0;this.yd=new Tc(b);this.ma=0;this.e("Connection created");Yc(this)}
			*/
			if(preg_match("/if\(/", $matches[0]) && preg_match("/this\./", $matches[0]) && preg_match('/function/', $matches[0])){
				return $matches[0];
			}

			// <script defer src="//:" id="__onload_ie_pixastic__">\x3c/script>
			if(preg_match("/x3c\/script>/i", $matches[0])){
				return $matches[0];
			}

			return '';
		}

		if(preg_match("/<\/script>/", $matches[0])){
			return preg_replace("/\/\/[^\<]+<\/script>/", '</script>', $matches[0]);
		}

		return '';
	}

	static function minify_js_in_body($exclude_rules = false){
		global $speedycache;
		
		$data = $speedycache->enhanced['html'];
		$script_list = array();
		$script_start_index = false;

		for($i = 0; $i < strlen( $data ); $i++){
			if(isset($data[$i - 6])){
				if(substr($data, $i - 6, 7) == '<script'){
					$script_start_index = $i - 6;
				}
			}

			if(isset($data[$i - 8]) && !empty($script_start_index)){
				if(substr($data, $i - 8, 9) == '</script>'){
					array_push($script_list, array('start' => $script_start_index, 'end' => $i));
					$script_start_index = false;
				}
			}
		}

		if(empty($script_list)){
			return $speedycache->enhanced['html'];
		}
		
		foreach(array_reverse($script_list) as $key => $value){
			$script_tag = substr($data, $value['start'], ($value['end'] - $value['start'] + 1));

			if(!preg_match("/^<script[^\>\<]+src\=[^\>\<]+>/i", $script_tag) && preg_match("/\/speedycache\-assets\//i", $script_tag)){
				continue;
			}

			preg_match("/src\=[\"\']([^\'\"]+)[\"\']/i", $script_tag, $src);

			$http_host = str_replace(array('http://', 'www.'), '', sanitize_text_field($_SERVER['HTTP_HOST']));
			
			if(!isset($src[1])){
				continue;
			}

			if(!preg_match('/'.preg_quote($http_host, '/').'/i', $src[1])){
				continue;
			}

			if(!empty($exclude_rules)){
				$is_excluded = false;

				foreach((array)$exclude_rules as $exclude_key => $exclude_value){
					if(!empty($exclude_value['prefix']) && $exclude_value['type'] === 'js'){
						if($exclude_value['prefix'] === 'contain'){
							$preg_match_rule = preg_quote($exclude_value['content'], '/');
						}

						if(preg_match('/'.$preg_match_rule.'/i', $src[1])){
							$is_excluded = true;
							break;
						}
					}
				}

				if(!empty($is_excluded)){
					continue;
				}
			}
			
			// Skip if the file is already minified.
			if(strpos($src[1], '.min.') !== FALSE){
				continue;
			}

			if(preg_match("/alexa\.com\/site\_stats/i", $src[1])){
				continue;
			}

			if(preg_match("/wp-spamshield\/js\/jscripts\.php/i", $src[1])){
				continue;
			}

			//amazonjs/components/js/jquery-tmpl/jquery.tmpl.min.js?ver=1.0.0pre
			if(preg_match("/jquery-tmpl\/jquery\.tmpl\.min\.js/i", $src[1])){
				continue;
			}

			//<script src="https://server1.opentracker.net/?site=www.site.com"></script>
			if(preg_match("/[\?\=].*".preg_quote($http_host, '/').'/i', $src[1])){
				continue;
			}
			
			$js_file_name = md5($src[1]);

			$cache_file_path = WP_CONTENT_DIR.'/'.$speedycache->enhanced['cache_speedycache_minified'].'/'.$js_file_name;
			
			if(!defined('SPEEDYCACHE_WP_CONTENT_URL')){
				$js_script = content_url().'/'.$speedycache->enhanced['cache_speedycache_minified'].'/'.$js_file_name;
			}else{
				$js_script = SPEEDYCACHE_WP_CONTENT_URL.'/'.$speedycache->enhanced['cache_speedycache_minified'].'/'.$js_file_name;
			}

			$js_script = str_replace(array('http://', 'https://'), '//', $js_script);
			
			$args = array(
				'src' => $src[1],
				'cache_file_path' => $cache_file_path,
				'js_script' => $js_script,
				'script_tag' => $script_tag,
				'value' => $value
			);
			
			self::fetch_and_minify_js($args);
		}

		return $speedycache->enhanced['html'];
	}


	static function fetch_and_minify_js($args){
		global $speedycache;
		
		$response = wp_remote_get(\SpeedyCache\Enhanced::fix_protocol($args['src']), array('timeout' => 10 ) );

		if(empty($response) || is_wp_error($response)){
			return false;
		}

		if(wp_remote_retrieve_response_code($response) != 200){
			return false;
		}

		$js_content = wp_remote_retrieve_body($response);

		if(preg_match('/<\/\s*html\s*>\s*$/i', $js_content)){
			return false;
		}
		
		$minified_js_content = \SpeedyCache\Enhanced::minify_js($js_content);

		if(!is_dir($args['cache_file_path'])){
			$prefix = time();
			\SpeedyCache\Cache::create_dir($args['cache_file_path'], $minified_js_content, 'js');
		}

		if(file_exists($args['cache_file_path']) && $js_files = @scandir($args['cache_file_path'], 1)){
			$new_script = str_replace($args['src'], $args['js_script'].'/'.$js_files[0], $args['script_tag']);
			$speedycache->enhanced['html'] = substr_replace($speedycache->enhanced['html'], $new_script, $args['value']['start'], ($args['value']['end'] - $args['value']['start'] + 1));
		}

	}

	static function combine_js_in_footer($minify = false){
		global $speedycache;
		
		$footer = strstr($speedycache->enhanced['html'], '<!--SPEEDYCACHE_FOOTER_START-->');

		\SpeedyCache\JS::init($footer, $minify);
		$tmp_footer = \SpeedyCache\JS::combine();
		
		if(!empty($speedycache->options['render_blocking'])){
			\SpeedyCache\RenderBlocking::init($tmp_footer);
			$tmp_footer = \SpeedyCache\RenderBlocking::action(false, true);
		}
		
		$speedycache->enhanced['html'] = str_replace($footer, $tmp_footer, $speedycache->enhanced['html']);
		
		return $speedycache->enhanced['html'];
	}
	/* Js Part End */

	static function fix_protocol($url){
		if(!preg_match('/^\/\//', $url)){
			return $url;
		}

		if(preg_match('/^https:\/\//', home_url())){
			return 'https:'.$url;
		}

		return 'http:'.$url;
	}

	static function set_html($html){
		global $speedycache;
		
		$speedycache->enhanced['html'] = $html;
		self::set_head_html();
		self::set_body_html();
	}

	static function set_body_html(){
		global $speedycache;
		
		preg_match("/<body(.+)<\/body>/si", $speedycache->enhanced['html'], $out);

		if(isset($out[0])){
			$speedycache->enhanced['body_html'] = $out[0];
			return;
		}

		$speedycache->enhanced['body_html'] = '';
	}

	static function set_head_html(){
		global $speedycache;
		
		preg_match("/<head(.+)<\/head>/si", $speedycache->enhanced['html'], $out);

		if(isset($out[0])){
			$speedycache->enhanced['head_html'] = $out[0];
			return;
		}

		$speedycache->enhanced['head_html'] = '';

	}
	
	static function delay_js($content){
		global $speedycache;

		// If Delay js mode is selected and the scripts are empty then return
		if(empty($speedycache->options['delay_js_mode']) || (!empty($speedycache->options['delay_js_mode']) && $speedycache->options['delay_js_mode'] == 'selected' && empty($speedycache->options['delay_js_scripts']))){
			return $content;
		}
		
		$scripts = self::find_tags('<script', '</script>', $content);
		
		if(empty($scripts)){
			return $content;
		}

		foreach($scripts as $tag => $script){
			// Dont process a tag without src
			if(strpos($script['text'], ' src') === FALSE){
				continue;
			}
			
			// We dont want to delay jQuery
			if(preg_match('/jquery\./U', $script['text'], $match)){
				continue;
			}
			
			// Excluding Scripts
			if($speedycache->options['delay_js_mode'] == 'all' && !empty($speedycache->options['delay_js_excludes'])){
				$script_matched = false;
				foreach($speedycache->options['delay_js_excludes'] as $to_delay){
					if(strpos($script['text'], $to_delay) !== FALSE){
						$script_matched = true;
					}
				}

				if(!empty($script_matched)){
					continue;
				}
			}

			// Delay Selected Scripts
			if($speedycache->options['delay_js_mode'] == 'selected' && !empty($speedycache->options['delay_js_scripts'])){
				$script_found = false;
				foreach($speedycache->options['delay_js_scripts'] as $to_delay){
					if(strpos($script['text'], $to_delay) !== FALSE){
						$script_found = true;
						break;
					}
				}

				if(empty($script_found)){
					continue;
				}
			}

			$new_tag = self::updating_tag($script['text']);

			if(!empty($new_tag)){
				$content = str_replace($script['text'], $new_tag, $content);
			}
		}
		
		// Adds the script which loads the JS files on user interaction
		self::inject_js($content);

		return $content;

	}
	
	static function updating_tag($tag){
		global $speedycache;
	
		if(preg_match('/src=["\'](.*)["\']/U', $tag, $src)){
			return '<script type="speedycache/javascript" data-src="' . $src[1] . '"></script>';
		}

	}
	
	static function inject_js(&$content){
		$js = file_get_contents(SPEEDYCACHE_PRO_DIR . '/assets/js/delayjs.min.js');

		$js = '<script>'.$js.'</script>';
		$content = str_replace('</body>', $js . "\n</body>", $content);
	}
	
	static function find_tags($start_string, $end_string){
		global $speedycache;
		
		$data = $speedycache->enhanced['html'];

		$list = array();
		$start_index = false;
		$end_index = false;

		for($i = 0; $i < strlen($data); $i++){
			if(substr($data, $i, strlen($start_string)) == $start_string){
				$start_index = $i;
			}

			if($start_index && $i > $start_index && substr($data, $i, strlen($end_string)) == $end_string){
				$end_index = $i + strlen($end_string) - 1;
				$text = substr($data, $start_index, ($end_index - $start_index + 1));

				array_push($list, array('start' => $start_index, 'end' => $end_index, 'text' => $text));

				$start_index = false;
				$end_index = false;
			}
		}

		return $list;
	}
	
	// Adds Image dimensions to the images where height and width is not present
	// It helps in reducing the Cumulative Layout shift(CLS) as the browser knows how much space to allocate for the image.
	static function image_dimensions($content){
		
		if(!function_exists('getimagesize')){
			return $content;
		}
		
		// Get Images without height and width
		$images_regex = '<img(?:[^>](?!height=[\'\"](?:\S+)[\'\"]))*+>|<img(?:[^>](?!width=[\'\"](?:\S+)[\'\"]))*+>';

		preg_match_all('/'.$images_regex.'/Uis', $content, $image_matches);

		if(empty($image_matches)){
			return $content;
		}

		$images = $image_matches[0];
		$site_url = site_url();
		
		foreach($images as $image){

			// Get the SRC
			if(!preg_match( '/\s+src\s*=\s*[\'"](?<url>[^\'"]+)/i', $image, $src_match)){
				continue;
			}
			
			$url = $src_match['url'];

			// We will proccess image which is uploaded inside wp-content
			if(strpos($url, 'wp-content') === FALSE || strpos($url, '.svg') !== FALSE || strpos($url, $site_url) === FALSE){
				continue;
			}

			$url = str_replace($site_url, '', $url);
			$image_path = str_replace('/wp-content', '', WP_CONTENT_DIR) . $url;
	
			if(!file_exists($image_path)){
				continue;
			}

			$sizes = getimagesize($image_path);

			if(empty($sizes)){
				continue;
			}

			preg_match('/<img.*\sheight=[\'\"]?(?<height>[^\'\"\s]+)[\'\"]?.*>/i', $image, $initial_height);
			preg_match('/<img.*\swidth=[\'\"]?(?<width>[^\'\"\s]+)[\'\"]?.*>/i', $image, $initial_width);

			$dimensions_attr = '';

			if(empty($initial_height['height']) && empty( $initial_width['width'])){
				$dimensions_attr = $sizes[3];
			}

			if(!empty($initial_height['height']) && empty($dimensions_attr)){
				if(!is_numeric($initial_height['height'])){
					continue;
				}

				$ratio = $initial_height['height'] / $sizes[1];

				$dimensions_attr = 'width="' . (int) round($sizes[0] * $ratio) . '" height="' . $initial_height['height'] . '"';
			}

			if(!empty($initial_width['width']) && empty($dimensions_attr)){
				if(!is_numeric( $initial_width['width'])){
					continue;
				}

				$ratio = $initial_width['width'] / $sizes[0];

				$dimensions_attr = 'width="' . $initial_width['width'] . '" height="' . (int) round($sizes[1] * $ratio) . '"';
			}
			
			$changed_image = preg_replace('/\s(height|width)=(?:[\'"]?(?:[^\'\"\s]+)*[\'"]?)?/i', '', $image);
			$changed_image = preg_replace('/<\s*img/i', '<img ' . $dimensions_attr, $changed_image);

			if(!empty($changed_image)){
				$content = str_replace($image, $changed_image, $content);
			}

		}
		
		return $content;
	}
	
	// Lazy Loads HTML elements.
	static function lazy_load_html($content){
		global $speedycache;

		$content = str_replace('</head>', '<style>' . implode(',', $speedycache->options['lazy_load_html_elements']) . '{content-visibility:auto;contain-intrinsic-size:1px 1000px;}</style></head>', $content);
		
		return $content;
	}
	
	static function preload_critical_images($content){
		global $speedycache;
		
		preg_match_all('#(<picture.*?)?<img([^>]+?)\/?>(?><\/picture>)?#is', $content, $images, PREG_SET_ORDER);

		if(empty($images)){
			return $content;
		}
		
		$count = 0;
		$preload_tags = '';
		foreach($images as $image){

			// Break once the Critical Image Count is reached.
			if($count >= $speedycache->options['critical_image_count']){
				break;
			}

			if(strpos($image[0], 'secure.gravatar.com') !== FALSE){
				continue;
			}

			// NOTE:: Will remove this in future, firt we will just support <IMG> tag
			if(strpos($image[0], '<picture>') !== FALSE){
				continue;
			}

			// Excluding base64 image from preloading.
			if(strpos($image[0], ';base64') !== FALSE){
				continue;
			}

			$atts_array = wp_kses_hair($image[2], wp_allowed_protocols());
			$atts = [];

			foreach($atts_array as $name => $attr){
				$atts[$name] = $attr['value'];
			}

			if(empty($atts['src'])){
				continue;
			}
			
			// To preload unique images.
			if(strpos($preload_tags, $atts['src']) === FALSE){
				$preload_tags .= '<link rel="preload" as="image" href="'.esc_attr($atts['src']).'"'. (!empty($atts['srcset']) ? ' imagesrcset="'. esc_attr($atts['srcset']).'"' : '') . (!empty($atts['sizes']) ? 'imagesizes="'.esc_attr($atts['sizes']).'"' : '') . ' />';
			}

			$count++;
		}

		if(empty($preload_tags)){
			return $content;
		}

		// If title tag is not there then don't add the preload.
		if(strpos($content, '</title>') === FALSE){
			return $content;
		}

		$content = str_replace('</title>', '</title>' . $preload_tags, $content);

		return $content;
	}
	
	static function pre_connect_hint($urls, $relation_type){
		global $speedycache;

		if($relation_type !== 'preconnect'){
			return $urls;
		}

		foreach($speedycache->options['pre_connect_list'] as $url) {
			if(empty($url) || empty($url['resource'])){
				continue;
			}
			
			$preconnect = array('href' => $url['resource']);

			if(!empty($url['crossorigin'])){
				$preconnect['crossorigin'] = 'crossorigin'; 
			}
			
			$urls[] = $preconnect;
			
		}

		return $urls;
	}

	static function preload_resource(){
		global $speedycache;
		
		if(empty($speedycache->options['preload_resource_list']) || !is_array($speedycache->options['preload_resource_list'])){
			return;
		}

		foreach($speedycache->options['preload_resource_list'] as $preload_resource){
			if(empty($preload_resource['resource']) || empty($preload_resource['type'])){
				continue;
			}
			
			$crossorigin = '';
			if(!empty($preload_resource['crossorigin'])){
				$crossorigin = 'crossorigin';
			}
			
			
			echo '<link rel="preload" href="'.esc_url_raw($preload_resource['resource']).'" as="'.esc_attr($preload_resource['type']).'" '.esc_attr($crossorigin) .'/>';
		}
	}

}
