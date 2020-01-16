<?php
/**
 * Plugin Name: Access For All - Inject Last Modified Date
 * Description: Injects a Shortcode into each Post in the appropriate location to always show the last modified date
 * Version: 1.0.2
 * Text Domain: accessforall-inject-last-modified-date
 * Author: Real Big Marketing
 * Author URI: https://realbigmarketing.com/
 * Contributors: d4mation
 * GitHub Plugin URI: disAbility-Connections/accessforall-inject-last-modified-date
 * GitHub Branch: master
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AccessForAll_Inject_Last_Modified_Date' ) ) {

	/**
	 * Main AccessForAll_Inject_Last_Modified_Date class
	 *
	 * @since	  1.0.0
	 */
	final class AccessForAll_Inject_Last_Modified_Date {
		
		/**
		 * @var			array $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			array $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true AccessForAll_Inject_Last_Modified_Date
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( version_compare( get_bloginfo( 'version' ), '4.4' ) < 0 ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires v%s of %sWordPress%s or higher to be installed!', 'First string is the plugin name, followed by the required WordPress version and then the anchor tag for a link to the Update screen.', 'accessforall-inject-last-modified-date' ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '4.4', '<a href="' . admin_url( 'update-core.php' ) . '"><strong>', '</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
				return false;
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );

			if ( ! defined( 'AccessForAll_Inject_Last_Modified_Date_VER' ) ) {
				// Plugin version
				define( 'AccessForAll_Inject_Last_Modified_Date_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'AccessForAll_Inject_Last_Modified_Date_DIR' ) ) {
				// Plugin path
				define( 'AccessForAll_Inject_Last_Modified_Date_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'AccessForAll_Inject_Last_Modified_Date_URL' ) ) {
				// Plugin URL
				define( 'AccessForAll_Inject_Last_Modified_Date_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'AccessForAll_Inject_Last_Modified_Date_FILE' ) ) {
				// Plugin File
				define( 'AccessForAll_Inject_Last_Modified_Date_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = AccessForAll_Inject_Last_Modified_Date_DIR . '/languages/';
			$lang_dir = apply_filters( 'accessforall_inject_last_modified_date_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), 'accessforall-inject-last-modified-date' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'accessforall-inject-last-modified-date', $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/accessforall-inject-last-modified-date/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/accessforall-inject-last-modified-date/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( 'accessforall-inject-last-modified-date', $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/accessforall-inject-last-modified-date/languages/ folder
				load_textdomain( 'accessforall-inject-last-modified-date', $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( 'accessforall-inject-last-modified-date', false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			// WP Batch Processing
			require_once AccessForAll_Inject_Last_Modified_Date_DIR . 'vendor/autoload.php';

			if ( class_exists( 'WP_Batch_Processor_Admin' ) ) {

				$admin_instance = WP_Batch_Processor_Admin::get_instance();

				// Remove some stuff we do not want
				remove_action( 'admin_menu', array( $admin_instance, 'admin_menu' ) );

			}

			require_once AccessForAll_Inject_Last_Modified_Date_DIR . 'core/batch-processing/class-accessforall-inject-last-modified-date-batch.php';

			add_action( 'wp_batch_processing_init', array( $this, 'register_batch' ), 15 );

			require_once AccessForAll_Inject_Last_Modified_Date_DIR . 'core/admin/class-accessforall-inject-last-modified-date-admin-page.php';
			$this->admin_page = new AccessForAll_Inject_Last_Modified_Date_Admin_Page();

		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				'accessforall-inject-last-modified-date',
				AccessForAll_Inject_Last_Modified_Date_URL . 'dist/assets/css/app.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : AccessForAll_Inject_Last_Modified_Date_VER
			);
			
			wp_register_script(
				'accessforall-inject-last-modified-date',
				AccessForAll_Inject_Last_Modified_Date_URL . 'dist/assets/js/app.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : AccessForAll_Inject_Last_Modified_Date_VER,
				true
			);
			
			wp_localize_script( 
				'accessforall-inject-last-modified-date',
				'accessforallInjectLastModifiedDate',
				apply_filters( 'accessforall_inject_last_modified_date_localize_script', array() )
			);
			
			wp_register_style(
				'accessforall-inject-last-modified-date-admin',
				AccessForAll_Inject_Last_Modified_Date_URL . 'dist/assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : AccessForAll_Inject_Last_Modified_Date_VER
			);
			
			wp_register_script(
				'accessforall-inject-last-modified-date-admin',
				AccessForAll_Inject_Last_Modified_Date_URL . 'dist/assets/js/admin.js',
				array( 'jquery' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : AccessForAll_Inject_Last_Modified_Date_VER,
				true
			);
			
			wp_localize_script( 
				'accessforall-inject-last-modified-date-admin',
				'accessforallInjectLastModifiedDate',
				apply_filters( 'accessforall_inject_last_modified_date_localize_admin_script', array() )
			);
			
		}

		/**
		 * This gets populated on our Ajax Callback from the contents of a Temp text file containing a JSON representation of what we are storing
		 * ...A little overly complicated sounding, but to get around server limitations and utilize this library it was about the only sane way to do it
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return  [void]
		 */
		public function register_batch() {

			$batch = new AccessForAll_Inject_Last_Modified_Date_Batch();
    		WP_Batch_Processor::get_instance()->register( $batch );

		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true AccessForAll_Inject_Last_Modified_Date
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \AccessForAll_Inject_Last_Modified_Date The one true AccessForAll_Inject_Last_Modified_Date
 */
add_action( 'plugins_loaded', 'accessforall_inject_last_modified_date_load' );
function accessforall_inject_last_modified_date_load() {

	require_once __DIR__ . '/core/accessforall-inject-last-modified-date-functions.php';
	ACCESSFORALLINJECTLASTMODIFIEDDATE();

}