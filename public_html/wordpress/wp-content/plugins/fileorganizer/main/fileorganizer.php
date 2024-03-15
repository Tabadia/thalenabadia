<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

if(!defined('FILEORGANIZER_VERSION')){
	die('Hacking Attempt!');
}

// The fileorganizer Header
function fileorganizer_page_header($title = 'FILE ORGANIZER'){
	
	global $fileorganizer;

	// Enqueue required scripts and styles
	wp_enqueue_script('forg-elfinder');
	wp_enqueue_script('forg-lang');
	wp_enqueue_style('forg-elfinder');
	wp_enqueue_style('forg-theme');
?>

<div class="fileorganizer_wrap">
	<table cellpadding="2" class="fileorganizer-header" cellspacing="1" width="100%" border="0">
		<tr>
			<td>
				<div class="fileorganizer-td">
					<img src="<?php echo FILEORGANIZER_URL ?>/images/logo.png" />
					<h3 class="fileorganizer-heading"><?php echo esc_html($title)?></h3>
				</div>
			</td>
			<?php 
			
			if(current_user_can('manage_options')){ 
				$theme = !empty($fileorganizer->options['theme']) ? $fileorganizer->options['theme'] : '';
			?>
			<td class="fileorganizer-options">
				<div class="fileorganizer-td">
					<label><?php _e('Theme'); ?></label>
					<select id="fileorganizer-theme-switcher"> 
						<option <?php  selected($theme, 'default'); ?> value=""><?php _e('Default'); ?></option>
						<option <?php  selected($theme, 'dark'); ?>  value="dark"><?php _e('Dark'); ?></option>
						<option <?php  selected($theme, 'material'); ?>  value="material"><?php _e('Material'); ?></option>
						<option <?php  selected($theme, 'material-dark'); ?>  value="material-dark"><?php _e('Material Dark'); ?></option>
						<option <?php  selected($theme, 'material-gray'); ?>  value="material-gray"><?php _e('Material Light'); ?></option>
						<option <?php selected($theme, 'windows10'); ?>  value="windows10"><?php _e('Windows 10'); ?></option>
					</select>
				</div>
			</td>
		<?php 
			}
		?>
		</tr>
	</table>

<?php
}

// Fileorganizer Settings footer
function fileorganizer_page_footer($no_twitter = 0){
	
	echo '</div>
	<div class="fileorganizer_footer_wrap">
		<a href="https://fileorganizer.net" target="_blank">'.__('FileOrganizer').'</a><span> v'.FILEORGANIZER_VERSION.' You can report any bugs </span><a href="https://wordpress.org/support/plugin/fileorganizer" target="_blank">here</a>.
	</div>';
	
}

function fileorganizer_render_page(){
	global $fileorganizer;

	echo '<div class="wrap">';

	fileorganizer_page_header();

	echo '<div id="fileorganizer_elfinder"></div>';

	fileorganizer_page_footer();
	
	// Editor configurations
	$elfinder_config = 'url: fileorganizer_ajaxurl,
		customData: {
			action: "fileorganizer_file_folder_manager",
			fileorganizer_nonce: fileorganizer_ajax_nonce,
		},
		defaultView: "'.(!empty($fileorganizer->options['default_view']) ? $fileorganizer->options['default_view'] : 'list').'",
		height: 500,
		lang: fileorganizer_lang,
		soundPath: fileorganizer_url+"/sounds/",
		cssAutoLoad : false,
		uploadMaxChunkSize: 1048576000000,
		baseUrl: fileorganizer_url,
		requestType: "post",
		commandsOptions: {
			edit : {
				mimes : [],
				editors : [{
					info : {
						id : "codemirror",
						name : "Code Editor",
					},
					mimes : [
						"text/plain",
						"text/html",
						"text/javascript",
						"text/css",
						"text/x-php",
						"application/x-php",
					],
					load : function(textarea) {
						var mimeType = this.file.mime;
						return wp.CodeMirror.fromTextArea(textarea, {
							mode: mimeType,
							indentUnit: 4,
							lineNumbers: true,
							viewportMargin: Infinity,
							lineWrapping: true,
						});
						
					},
					close : function(textarea, instance) {
						this.myCodeMirror = null;
					},
					save: function(textarea, editor) {							  
						jQuery(textarea).val(editor.getValue());
					}
				}]
			}
		},
		ui: ["toolbar", "tree", "path", "stat"],';

		$elfinder_uiOptions = 'uiOptions:{
			toolbarExtra : {
				autoHideUA: [],
				displayTextLabel: "none",
				preferenceInContextmenu: false,
			},
		},';

	$elfinder_config .= apply_filters('fileorganizer_elfinder_script', $elfinder_uiOptions);

?>
<script>
	
	var fileorganizer_ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
	var fileorganizer_ajax_nonce = "<?php echo wp_create_nonce( 'fileorganizer_ajax' ); ?>";
	var fileorganizer_url = "<?php echo FILEORGANIZER_URL; ?>/manager/";
	var fileorganizer_lang = "<?php echo !empty($fileorganizer->options['default_lang']) ? $fileorganizer->options['default_lang'] : 'en' ?>";
	
	jQuery(document).ready(function() {

		jQuery('#fileorganizer_elfinder').elfinder({
		<?php echo $elfinder_config; ?>
		}).elfinder("instance");
			
	<?php 	
		if(current_user_can('manage_options')){
	?>
		jQuery('#fileorganizer-theme-switcher').change(function(){
			var theme = jQuery(this).val();
			jQuery.ajax({
				url: fileorganizer_ajaxurl,
				data:{
					action: 'fileorganizer_switch_theme',
					fileorganizer_nonce: fileorganizer_ajax_nonce,
					theme: theme
				},
				dataType: 'json',
				type: 'post',
				success:function(resp){
					if(typeof resp.error != 'undefined'){
						alert(resp.error);
						return;
					}	
					
					if(resp.stylesheet != undefined){	
						jQuery('#forg-theme-css').attr('href', resp.stylesheet);
					}
				}
			});
		});
	<?php 
		}
	?>
	});

</script>
<?php
}