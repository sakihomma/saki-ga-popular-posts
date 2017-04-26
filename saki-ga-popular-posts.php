<?php
/*
Plugin Name: Saki GA Popular Posts
Plugin URI: https://github.com/sakihomma/saki-ga-popular-posts
Description: This plugin generates a list of the popular posts using Google Analytics Client API. 
Version: 0.1
Author: SAKI
Author URI: https://sakidesign.com
Text Domain: saki-ga-popular-posts
Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( "Cannot access pages directly." );

if( !class_exists( 'Saki_GA_Popular_Posts' ) ) :

	class Saki_GA_Popular_Posts {

		// Constants
		const name = "Saki GA Popular Posts";
		const slug = "saki-ga-popular-posts";
		const version = "1.0";
		const wpversion = "4.7";

		public $pluginName;
		public $pluginSlug;
		public $pluginDir;


		// Constructor
		public function __construct() {

		    // Define constants
		    $this->init_plugin_constants();

			// l10n
			load_plugin_textdomain( $this->pluginSlug, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			// Setting Plugin Page
			add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), array( $this, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_plugin_links' ), 10, 2 );

			// Plugin activation/deactivation callback
			register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );

			// Setting admin page
			add_action( 'admin_menu', array( $this, 'add_setting_page' ) ) ;
			add_action( 'admin_init', array( $this, 'setting_register_init' ) );

			// Core Functions
			require_once $this->pluginDir . '/saki-ga-popular-posts-sc.php';
		}

		/**
		 * Initializes constants
		 */
		private function init_plugin_constants() {
			$this->pluginName = self::name;
			$this->pluginSlug = self::slug;
			$this->pluginDir = untrailingslashit( dirname( __FILE__ ) );

			if( !defined( 'SAKIGAPP_PLUGIN_NAME' ) ) define( 'SAKIGAPP_PLUGIN_NAME', self::name );
			if( !defined( 'SAKIGAPP_PLUGIN_SLUG' ) ) define( 'SAKIGAPP_PLUGIN_SLUG', self::slug );
		}

		/**
		 * Add action links on plugin page in to Plugin Name block
		 * @param $links array() action links
		 * @return $links array() action links
		 */
		public function plugin_action_links( $links ) {
			$settings_link = '<a href="options-general.php?page=sakigapp_options">' . __( 'Settings', $this->pluginSlug ) . '</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 * Add action links on plugin page in to Plugin Description block
		 * @param $links array() action links
		 * @param $file  string  relative path to pugin "saki-ga-popular-posts/saki-ga-popular-posts-admin.php"
		 * @return $links array() action links
		 */
		public function plugin_plugin_links( $links, $file ) {
			$base = plugin_basename( __FILE__ );
			if( $file == $base ){
				$links[] = '<a href="options-general.php?page=sakigapp_options">' . __( 'Settings', $this->pluginSlug ) . '</a>';
			}
			return $links;
		}

		/**
		 * Performed at activation.
		 */
		public function plugin_activation() {
			//future use
		}

		/**
		 * Performed at deactivation.
		 */
		public function plugin_deactivation() {
			unregister_setting( 'sakigapp_options', 'sakigapp_gaapi' );
			unregister_setting( 'sakigapp_options', 'sakigapp_keyfile' );
			unregister_setting( 'sakigapp_options', 'sakigapp_viewid' );
		}

		/**
		 * Set setting page
		 */
		public function add_setting_page() {
			add_options_page( $this->pluginName, $this->pluginName, 'manage_options', 'sakigapp_options', array($this,'show_setting_page') );
		}

		/**
		 * Show setting page
		 */
		public function show_setting_page() {
?>
			<div class='wrap'>
			<h1><?php echo $this->pluginName; ?></h1>

			<?php if ( isset( $_GET['settings-updated'] ) ) delete_transient( 'saki_gapp' ); ?>

			<form  method="post" action="options.php">
				<?php settings_fields( 'sakigapp_options' ); ?>
				<?php do_settings_sections( 'sakigapp_options' ); ?>
				<?php submit_button( __( "Save Change", "default" ), "primary", "submit_save" ); ?>
			</form>

			<?php $this->show_cache_clear_form(); ?>

			<hr><p><b>SHORTCODE USAGE</b></p>
			<p>[sakigapp]<br>[sakigapp num=10 start=7daysAgo end=today] (default)</p>

			<hr><p>Thank you for using. I hope this will give you convenient plug-ins!</p>
			<p><a href='https://sakidesign.com' target='_blank'>https://sakidesign.com</a></p>
			</div><!--/.wrap-->

		<?php }

		/**
		 * Clear cached data..
		 */
		public function show_cache_clear_form() {

			if ( isset( $_POST['submit_cache_clear'] ) ):
				delete_transient( 'saki_gapp' );
				echo '<div class="updated fade"><p><strong>' . __( 'Cached data cleared.', $this->pluginSlug ) . '</strong></p></div>';
			endif; ?>

			<form  method="post" action="">
				<?php submit_button( __( "Clear Cached Data", $this->pluginSlug ), "secondary", "submit_cache_clear" ); ?>
			</form>

		<?php }

		/**
		 * Seeting register
		 */

		// init
		function setting_register_init() {
			add_settings_section( 'sakigapp_setting_section', __( 'Settings', $this->pluginSlug ), array( $this, 'setting_sakigapp_section' ), 'sakigapp_options' );

			add_settings_field( 'sakigapp_gaapi', 'Google API PHP Client Library autoload.php Location', array( $this, 'setting_sakigapp_gaapi' ), 'sakigapp_options', 'sakigapp_setting_section', array( 'label_for' => 'sakigapp_gaapi' ) );
			add_settings_field( 'sakigapp_keyfile', 'Private Key File Location', array( $this, 'setting_sakigapp_keyfile' ), 'sakigapp_options', 'sakigapp_setting_section', array( 'label_for' => 'sakigapp_keyfile' ) );
			add_settings_field( 'sakigapp_viewid', 'Google Analytics View ID', array( $this, 'setting_sakigapp_viewid' ), 'sakigapp_options', 'sakigapp_setting_section', array( 'label_for' => 'sakigapp_viewid' ) );

			register_setting( 'sakigapp_options', 'sakigapp_gaapi' );
			register_setting( 'sakigapp_options', 'sakigapp_keyfile' );
			register_setting( 'sakigapp_options', 'sakigapp_viewid' );
		}

		// section
		function setting_sakigapp_section() {
			echo "<p>First of all, if you don't have Google API PHP Client Library installed, you will need to install beforehand.<br>"
				."Then upload your private key file ( service-account-credentials.json ) to your webserver.</p>"
				."<p>Save change, the cache data of GA Popular Posts is deleted. You can forcibly clear it using the 'Clear Cached Data' button if necessary.</p>";
		}

		// field sakigapp_gaapi
		function setting_sakigapp_gaapi() {
?>
			<input name="sakigapp_gaapi" type="text" id="sakigapp_gaapi" value="<?php form_option( 'sakigapp_gaapi' ); ?>" class="regular-text" />
			<p class="description" id="sakigapp_gaapi-description">For example, /virtual/xxx/yyy/google-api-php-client/vendor/autoload.php</p>
<?php
		}

		// field sakigapp_keyfile
		function setting_sakigapp_keyfile() {
?>
			<input name="sakigapp_keyfile" type="text" id="sakigapp_keyfile" value="<?php form_option( 'sakigapp_keyfile' ); ?>" class="regular-text" />
			<p class="description" id="sakigapp_keyfile-description">For example, /virtual/xxx/yyy/google-api-php-client/service-account-credentials.json</p>
<?php
		}

		// field sakigapp_viewid
		function setting_sakigapp_viewid() {
?>
			<input name="sakigapp_viewid" type="text" id="sakigapp_viewid" value="<?php form_option( 'sakigapp_viewid' ); ?>" class="regular-text" />
			<p class="description" id="sakigapp_viewid-description">You can use the <a href="https://ga-dev-tools.appspot.com/account-explorer/" target="_blank">Account Explorer</a> to find a View ID.</p>
<?php
		}

	}

endif;

$Saki_GA_Popular_Posts = new Saki_GA_Popular_Posts();


/**
 * Performed at uninstal.
 */
register_uninstall_hook( __FILE__, 'sakigapp_plugin_uninstall' );
if (!function_exists( 'sakigapp_plugin_uninstall' ) ) {
	function sakigapp_plugin_uninstall() {
		delete_option( 'sakigapp_gaapi' );
		delete_option( 'sakigapp_keyfile' );
		delete_option( 'sakigapp_viewid' );
		delete_transient( 'saki_gapp' );
	}
}
?>