<?php
/**
Plugin Name: Shrtnr
Plugin URI: http://k-create.com/shrtnr
Description: URL shortener (think bit.ly or tinyurl.com but for YOUR domain) with the ability to change the pathname 
Version: 0.1
Author: Kris Hedstrom
Author URI: http://k-create.com
License: GPL2

This plugin requires PHP >= 5.2 and json_encode to be enabled

Copyright 2011  K-Create.com  (email : info@k-create.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include ('shrtnr-widget.php');

if(!class_exists(Shrtnr)) {
	
	class Shrtnr {
		
		/**
		 * Initialize variables
		 */
		var $pluginVersion = "0.1";
		var $pluginVersionName = "shrtnr_version";
		var $pluginBasePath;
		var $pluginResourcePath;
		var $tableName = 'shrtnr';
		
		/**
		 * Constructor
		 */
		function __construct() {
			$this->pluginBasePath = WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__), "", plugin_basename(__FILE__));
			$this->pluginResourcePath = $this->pluginBasePath . '_ui/';
		}
		
		function init() {
			add_option($this->pluginVersionName, $this->pluginVersion);
			$this->createTables($this->tableName);
		}
		
		/**
		 * Register shrtnr settings fields
		 */
		function register() {
			register_setting('shrtnr_settings', 'shrtnr_settings', array($this, 'validateSettings'));
		}
		
		/*
		 * On deactivation delete options
		 * This does not delete the db tables, which happens on complete unistall. See uninstall.php
		 */
		function deactivate() {
			delete_option($this->pluginVersionName);
		}
		
		/**
		 * Check for existence of the db table [prefix]shrtnr,
		 * if it's not there create a new one.
		 */
		function createTables($table = "shrtnr") {
			global $wpdb;
			$tableName = $wpdb->prefix . $table;
			if ($wpdb->get_var("show tables like '$tableName'") != $tableName) {
				$sql = "CREATE TABLE " . $tableName . " (
					id BIGINT(20) NOT NULL AUTO_INCREMENT,
					post_id INT NOT NULL,
					short_url VARCHAR(255) NOT NULL,
					UNIQUE KEY id (id)
				);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$statsTableName = $wpdb->prefix . $table . "_stats";
			if ($wpdb->get_var("show tables like '$tableName'") != $statsTableName) {
				$sql = "CREATE TABLE " . $statsTableName . " (
					short_url varchar(255) NOT NULL,
					delta int(11) NOT NULL,
					timestamp datetime NOT NULL,
					referrer text
				);";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			update_option($this->pluginVersionName, $this->pluginVersion);
		}
		
		
		/**
		 * -----------------------------
		 * Admin panel
		 * -----------------------------
		 */
		
		function addHeaderItems() {
			// add resources (scripts + stylesheets)
			if (function_exists('wp_enqueue_script')) {
				$nonce = wp_create_nonce('shrtnr');
				wp_enqueue_script('shrtnr', $this->pluginResourcePath . "js/shrtnr.js");
				wp_enqueue_script('shrtnrTest', $this->pluginResourcePath . "js/shrtnr-settings.js");
				wp_localize_script( 'shrtnr', 'shrtnr_data', array('action' => 'shrtnr', '_ajax_nonce' => $nonce, 'editBtnText' => __('Edit'), 'cancelText' => __('Cancel')));
			}
			echo '<link type="text/css" rel="stylesheet" href="' . $this->pluginResourcePath . 'css/shrtnr.css" />' . "\n";
		}
		
		function settings() {
			// settings panel
		?>	
			<div class="wrap">
				 <?php screen_icon("options-general"); ?>
				<h2><?php _e('Shrtnr Settings', 'shrtnr'); ?></h2>
				<form method="post" action="options.php" id="shrtnr-settings">
					<?php settings_fields('shrtnr_settings'); ?>
					<?php $options = get_option('shrtnr_settings'); ?>
					<ul id="shrtnr-settings-tabs">
						<li><h3><a href="#shrtnr-settings-host"><?php _e('Host', 'shrtnr'); ?></a></h3></li>
						<li><h3><a href="#shrtnr-settings-path"><?php _e('Path', 'shrtnr'); ?></a></h3></li>
					</ul>
					<div class="clear"></div>
					<div id="shrtnr-settings-host" class="shrtnr-sections">
						<ul class="options">
							<li>
								<label>
									<input type="radio" name="shrtnr_settings[domain_type]" class="shrtnr-domain" value="regular"<?php if($options['domain_type'] != 'custom'){ echo ' checked="checked"';} ?> /> 
									<?php _e('Your Regular Domain', 'shrtnr'); ?> (<span id="shtrnr-regular-url"><?php bloginfo('url'); ?></span>)
								</label>
							</li>
							<li>
								<label>
									<input type="radio" name="shrtnr_settings[domain_type]" class="shrtnr-domain" id="shrtnr-custom-url" value="custom"<?php if($options['domain_type'] == 'custom'){ echo ' checked="checked"';} ?> /> 
									<?php _e('Custom Domain', 'shrtnr'); ?>
								</label>
								<div id="shrtnr-custom">
									<label for="shrtnr-custom-domain"><?php _e('Custom Domain', 'shrtnr'); ?></label>
									<input id="shrtnr-custom-domain" name="shrtnr_settings[domain]" placeholder="http://shrt.url" type="url" value="<?php echo $options['domain']; ?>" />
								</div>
							</li>
						</ul>
					</div>
					<div id="shrtnr-settings-path" class="shrtnr-sections">
						<ul class="options">
							<li>
								<label for="shrtnr-default-length"><?php _e('Default Length of Short URL Path'); ?></label>
								<input id="shrtnr-default-length" name="shrtnr_settings[default_length]" type="number" min="2" max="10" value="<?php if(!isset($options['default_length'])){ echo '5';} else { echo $options['default_length']; } ?>" />
							</li>
							<li>
								<label for="shrtnr-lowercase"><?php _e('Use Lowercase Letters?'); ?></label>
								<input id="shrtnr-lowercase" name="shrtnr_settings[use_lower]" type="checkbox" value="1" <?php checked('1', $options['use_lower']); if(!isset($options['use_lower']) && !isset($options['use_upper']) && !isset($options['use_numbers'])){ echo ' checked="checked"';} ?> />
							</li>
							<li>
								<label for="shrtnr-uppercase"><?php _e('Use Uppercase Letters?'); ?></label>
								<input id="shrtnr-uppercase" name="shrtnr_settings[use_upper]" type="checkbox" value="1" <?php checked('1', $options['use_upper']); ?> />
							</li>
							<li>
								<label for="shrtnr-numbers"><?php _e('Use Numbers?'); ?></label>
								<input id="shrtnr-numbers" name="shrtnr_settings[use_numbers]" type="checkbox" value="1" <?php checked('1', $options['use_numbers']); ?> />
							</li>
						</ul>
					</div>
					<div id="shrtnr-preview">
						<p><?php _e('This is an example what your short URL will look like:', 'shrtnr'); ?></p>
						<p id="shrtnr-preview-full">
							<span id="shrntr-preview-url"></span><span id="shrntr-preview-code"></span>
						<p>
					</div>
					<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
			</div>
		<?php
		}

		function validateSettings($input) {
			// Checkboxes are either 0 or 1
			$input['use_lower'] = $input['use_lower'] == 1 ? 1 : 0;
			$input['use_upper'] = $input['use_upper'] == 1 ? 1 : 0;
			$input['use_numbers'] = $input['use_numbers'] == 1 ? 1 : 0;
			if(!$input['use_lower'] && !$input['use_upper'] && !$input['use_numbers']) {
				add_settings_error('shrtnr_settings_use_lower', 'shrtnr_error', __('Short domain needs to use lower, upper or number characters (or a combination thereof)'), 'error');
			}
			
			// Domain must be safe text with no HTML tags
			$input['domain'] = wp_filter_nohtml_kses($input['domain']);
			return $input;
		}
		
		function sidebars() {
			if(function_exists('add_meta_box')) {
				add_meta_box('shrtnr_url', __('Short URL (Shrtnr)', 'shrtnr'), array(&$this, 'generateSidebars'), 'post', 'side' );
				add_meta_box('shrtnr_url', __('Short URL (Shrtnr)', 'shrtnr'), array(&$this, 'generateSidebars'), 'page', 'side' );
			}
			else {
				add_action('dbx_post_sidebar', array(&$this, 'generateSidebars'));
				add_action('dbx_page_sidebar', array(&$this, 'generateSidebars'));
			}
		}
		
		function generateSidebars() {
			global $wp_query;
			global $wpdb;
			$options = get_option('shrtnr_settings');
			$post_id = $wpdb->escape($_GET['post']);
			$tableName = $wpdb->prefix . $this->tableName;
			// Use nonce for verification
			wp_nonce_field(plugin_basename(__FILE__), 'shrtnr_nonce');
			
			if(!$post_id) {
				// nothing yet, so let's generate a URL
				$shrt = $this->generateURL();
				$post_id = '-1';
			} else {
				// attempt to get short URL from db
				$shrt = $wpdb->get_var("SELECT short_url FROM ". $tableName . " WHERE post_id = '" . $post_id . "'");
				// if one doesn't exists, create new one
				if(!$shrt) {
					$shrt = $this->generateURL();
				}
			}
			echo '<input type="hidden" id="shrtnr-post-id" name="shrtnr-post-id" value="' . $post_id . '" />';
			echo '<p><label for="shrtnr-url" class="screen-reader-text">' . __('Short URL', 'shrtnr' ) . '</label>';
			echo rtrim($options['domain'], '/') . '/';
			echo '<input type="text" id="shrtnr-url" name="shrtnr-url" value="' . $shrt . '" />';
		}
		/*** End Admin panel ***/
		
		function intl() {
			// Internationalize
			load_plugin_textdomain('shrtnr', false, dirname(plugin_basename( __FILE__ )) . '/langs/');
		}
		
		function savePost($post_id) {
			global $wpdb;
			$tableName = $wpdb->prefix . $this->tableName;
			
			// verify this came from the our screen and with proper authorization
			if (!wp_verify_nonce($_POST['shrtnr_nonce'], plugin_basename(__FILE__))) {
				return $post_id;
			}

			// Check permissions
			if ('page' == $_POST['post_type']) {
				if (!current_user_can('edit_page', $post_id)) {
					return $post_id;
				}
			} else {
				if (!current_user_can('edit_post', $post_id)) {
					return $post_id;
				}
			}

			// check for revisions or autosaves
			if(wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
				return $post_id;
			}

			// check if short url is valid and not taken
			$shrt = $wpdb->escape($_POST['shrtnr-url']);
			if(!$this->checkValidity($shrt, $post_id)) {
				// @TODO: figure out best way to display error message
				// in the meantime, generate new url
				$shrt = $this->generateURL();
			} 

			$current = $wpdb->get_var("SELECT short_url FROM " . $tableName . " WHERE post_id = '" . $post_id . "'");
			if($current) {
				// does post_id already have a short url? if so update the record
				$wpdb->query($wpdb->prepare("UPDATE $tableName SET short_url = %s WHERE post_id = %d", $shrt, $post_id));
			} else {
				// otherwise insert new record
				$wpdb->query($wpdb->prepare("INSERT INTO $tableName(post_id, short_url) VALUES (%d, %s)", $post_id, $shrt));
			}
			
			return $post_id;
		}
		
		function ajaxHandler() {
			// check refer(r)er
			check_ajax_referer("shrtnr");
			$postID = $_GET['postID'];
			$url = $_GET['url'];
			$response = '';
			if(!$this->checkValidity($url, $postID)) {
				// warn user that the url is not valid (or taken)
				if(function_exists(json_encode)) {
					$response = array('code' => 0, 'msg' => __('Not a valid short url, or it is already taken', 'shrtnr'));
				}
			} else {
				// Valid! We don't really need to do anything other than to say good job
				if(function_exists(json_encode)) {
					$response = array('code' => 1, 'msg' => __('Updated!', 'shrtnr'));
				}
			}
			echo json_encode($response);
			// WordPress will end the response by calling die('0'). If you don’t want that 0 to appear at the end of your response, 
			// you have to call die before WordPress gets a chance.
			die();
		}
		
		function generateString() {
			$options = get_option('shrtnr_settings');
			$chars = '';
			
			$length = $options['default_length'];

			if($options['use_lower']) {
				$chars = $chars . "abcdefghijklmnopqrstuvwxyz";
			}
			if($options['use_upper']) {
				$chars = $chars . "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			}
			if($options['use_numbers']) {
				$chars = $chars . "0123456789";
			}

			for ($i = 0, $str = ''; $i < $length; $i++) {
				$str .= $chars[mt_rand(0, strlen($chars) - 1)];
			}
			return $str;
		}
		
		/**
		 * Generate the URL, checks if url already exists
		 */
		function generateURL() {
			// check if string is already in database table
			$valid = false;
			while(!$valid) {
				$str = $this->generateString();
				$valid = $this->checkValidity($str);
			}
			return $str;
			
		}
		
		function checkValidity($str, $id = '-1') {
			global $wpdb;
			$tableName = $wpdb->prefix . $this->tableName;
			if(strlen($str) < 2 || strlen($str) > 10) {
				return false;
			}
			
			$short = $wpdb->get_row("SELECT short_url FROM " . $tableName . " WHERE short_url = '" . $str . "'");
			if(count($short) == 0) {
				return true;
			}

			if($id != '-1') {
				$short = $wpdb->get_row("SELECT short_url FROM " . $tableName . " WHERE short_url = '" . $str . "'");
				if(count($short) > 0) {
					// short url already exists check if url belongs to this post_id, if so it's okay
					$shortID = $wpdb->get_row("SELECT short_url FROM " . $tableName . " WHERE short_url = '" . $str . "' AND post_id = '" . $id . "'");
					if(count($shortID) > 0) {
						return true;
					}
				}
			}
			// return false if conditions above are not met
			return false;
		}
		
		// this method does the actual redirect
		function redirect() {
			global $wpdb;
			global $post;
			$tableName = $wpdb->prefix . $this->tableName;

			$request = $_SERVER['REQUEST_URI'];
			//echo $request;
			$shrt = trim($request);
			$shrt = trim($shrt, "/");
			// get rid of any slashes
			$shrt_split = explode('/', $shrt); 
			// get the last matched item
			$shrt = $shrt_split[count($shrt_split) - 1];
			
			$post_id = $wpdb->get_var("SELECT post_id FROM $tableName WHERE short_url = '" . $shrt . "'");
			if($post_id) {
				$perm = get_permalink($post_id);
				$perm = trim($perm, "/");
				// get rid of any slashes
				$perm_split = explode('/', $perm); 
				// get the last matched item
				$perm = $perm_split[count($perm_split) - 1];
				// try to avoid redirect loop
				if($perm != $shrt) {
					header('Location: '. get_permalink($post_id), true, 302);
					exit;
				}
			}
		}
		
		// display short URL (for use in templates)
		function getShrtLink($post_id) {
			global $wpdb;
			$tableName = $wpdb->prefix . $this->tableName;
			$path = $wpdb->get_var("SELECT short_url FROM $tableName WHERE post_id = '" . $post_id . "'");
			$options = get_option('shrtnr_settings');
			
			$host = ($options['domain_type'] == 'custom') ? $options['domain'] : get_bloginfo('url');
			return $host . '/' . $path;
		}
		
		// short code handler
		function _getShrt($atts, $content = null) {
			global $wpdb, $post;
			extract(shortcode_atts(array(
				'foo' => 'something'
				// ...etc
			), $atts ));
			return $this->getShrtLink($post->ID);   
		}
		
	}
	
}

if (class_exists("Shrtnr")){
	$shrtnr = new Shrtnr();
}

/**
 * Initialize admin panel
 */
if (!function_exists("shrtnr_ap")) {
	function shrtnr_ap() {
		global $shrtnr;
		if (!isset($shrtnr)) {
			return;
		}
		if (function_exists('add_options_page') && current_user_can('edit_plugins')) {
			$page = add_options_page("Shrtnr", "Shrtnr", 8, 'shrtnr', array(&$shrtnr, 'settings'));
			// add_action("admin_print_scripts-$page", array(&$shrtnr, 'addHeaderItems'));
			// add scripts to all admin pages
			add_action("admin_print_scripts", array(&$shrtnr, 'addHeaderItems'));
		}
	}	
}

/**
 * Actions, filters, registering the plugin
 */ 
if(isset($shrtnr)) {
	add_action('init', array($shrtnr, 'redirect'));
	add_action('admin_menu', 'shrtnr_ap');
	add_action('admin_menu', array($shrtnr, 'sidebars'));
	add_action('admin_init', array($shrtnr, 'register'));
	add_action('save_post', array($shrtnr, 'savePost'));
	add_action('wp_ajax_shrtnr', array($shrtnr, 'ajaxHandler'));
	add_shortcode('shrtnr', array($shrtnr, '_getShrt'));
	// @TODO: probably don't need any custom query vars, so delete
	// add_filter('query_vars', array(&$shrtnr, 'add_my_var')); 
	add_action('init', array($shrtnr, 'intl'));
	register_activation_hook(__FILE__, array($shrtnr, 'init'));
	register_deactivation_hook(__FILE__, array($shrtnr, 'deactivate'));
	// bug in Wordpress doesn't allow for method passing for cleanup, using uninstall.php for now
	// register_uninstall_hook(__FILE__, array($shrtnr, 'cleanup'));
}
