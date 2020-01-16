<?php
/**
 * The admin settings page for the Access For All - Inject Last Modified Date
 *
 * @since 1.0.0
 *
 * @package AccessForAll_Inject_Last_Modified_Date
 * @subpackage AccessForAll_Inject_Last_Modified_Date/core/admin
 */

defined( 'ABSPATH' ) || die();

final class AccessForAll_Inject_Last_Modified_Date_Admin_Page {

	/**
	 * AccessForAll_Inject_Last_Modified_Date_Admin_Page constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		add_filter( 'accessforall_inject_last_modified_date_localize_admin_script', array( $this, 'localize_script' ), 1 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        add_action( 'wp_ajax_accessforall_inject_last_modified_date_create_batch', array( $this, 'create_batch' ) );

	}

	/**
	 * Add the Submenu Page
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function add_submenu_page() {

		add_submenu_page(
			'tools.php',
			__( 'Access For All - Inject Last Modified Date', 'accessforall-inject-last-modified-date' ), // Page Title
			__( 'Inject Last Modified Date', 'accessforall-inject-last-modified-date' ), // Submenu Tite
			'manage_options',
			'accessforall-inject-last-modified-date',
			array( $this, 'page_content' )
        );

	}
    
    /**
     * Localize Ajax URL to our JS
     *
     * @param   array  $l10n  Localized Variables
     *
     * @access  public
     * @since   1.0.0
     * @return  array         Localized Variables
     */
    public function localize_script( $l10n ) {

		$l10n['ajaxUrl'] = admin_url( 'admin-ajax.php' );

		return $l10n;

    }

	/**
	 * Adds our CSS/JS to the Settings Page
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function enqueue_scripts() {

        global $current_screen;

		if ( $current_screen->base == 'tools_page_accessforall-inject-last-modified-date' ) {

			wp_enqueue_script( 'accessforall-inject-last-modified-date-admin' );

            wp_enqueue_style( 'accessforall-inject-last-modified-date-admin' );
            
            // We need to explicitly load the JS for the Ajax processing of our Batch

            wp_enqueue_script(
                'wp-batch-processing',
                WP_BP_URL . 'assets/processor.js',
                array( 'jquery' ),
                filemtime( WP_BP_PATH . 'assets/processor.js' ),
                true
            );

            wp_localize_script( 'wp-batch-processing', 'DgBatchRunner', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wp-batch-processing' ),
                'batch_id' => 'accessforall_inject_last_modified_date', // Hardcoded for our page
                'delay'    => apply_filters( 'wp_batch_processing_delay', 0 ), // Set delay in seconds before processing the next item. Default 0. No delay.
                'text'     => array(
                    'processing' => __( 'Processing...', 'wp-batch-processing' ),
                    'start'      => __( 'Start', 'wp-batch-processing' ),
                )
            ) );

		}

	}

	/**
     * Create the Content/Form for our Admin Page
     *
     * @access      public
     * @since       1.0.0
     * @return      void
     */
    public function page_content() { ?>

        <div class="wrap accessforall-inject-last-modified-date-settings">

            <h2><?php _e( 'Inject Last Modified Date', 'accessforall-inject-last-modified-date' ); ?></h2>

            <ul style="list-style-type: disc; margin-left: 1rem;">
                <li><?php _e( 'Clicking "Start Process" will begin the process. It will take a moment while a list of Posts on your site is compiled before beginning.', 'accessforall-inject-last-modified-date' ); ?></li>
            </ul>

			<form id="accessforall-inject-last-modified-date-form" method="post">

				<?php echo wp_nonce_field( 'accessforall_inject_last_modified_date_data', 'accessforall_inject_last_modified_date_nonce' ); ?>

                <button id="batch-process-start" style="display: none;"></button>

				<?php

					// WordPress automatically gives the Submit Button an ID and Name of "submit"
					// This is not necessary and causes problems with jQuery submit()
					// https://api.jquery.com/submit/ under "Additional Notes"
					submit_button( __( 'Start Process', 'accessforall-inject-last-modified-date' ), 'primary', false );

				?>

			</form>

        </div>

		<div id="accessforall-inject-last-modified-date-modal" class="reveal" data-reveal>

            <h2><?php _e( 'Progress:', 'accessforall-inject-last-modified-date' ); ?></h2>
            
            <h5><?php _e( 'Closing this window will halt the process. You can resume it by clicking the "Start Process" button again.', 'accessforall-inject-last-modified-date' ); ?></h5>

			<div class="batch-process">

                <div class="batch-process-main">
                    
                    <ul class="batch-process-stats">
                        <li><strong><?php _e( 'Total:', 'accessforall-inject-last-modified-date' ); ?></strong> <span id="batch-process-total">0</span></li>
                        <li><strong><?php _e( 'Processed:', 'accessforall-inject-last-modified-date' ); ?></strong> <span id="batch-process-processed">0</span> <span id="batch-process-percentage">(0%)</span></li>
                    </ul>
                    <div class="batch-process-progress-bar">
                        <div class="batch-process-progress-bar-inner" style=""></div>
                    </div>
                    <div class="batch-process-current-item">

                    </div>
                </div>
                <div class="batch-process-actions">
                    <button class="button-primary" id="batch-process-restart"><?php _e( 'Restart Batch Progress (You will need to click "Start Migration" again)', 'accessforall-inject-last-modified-date' ); ?></button>
                </div>
            </div>

            <div id="batch-errors" style="display: none;">
                <h3><?php _e( 'List of errors', 'accessforall-inject-last-modified-date' ); ?></h3>
                <ol id="batch-errors-list">

                </ol>
            </div>

			<button id="batch-process-stop" class="close-button" data-close aria-label="<?php _e( 'Close modal', 'accessforall-inject-last-modified-date' ); ?>" type="button">
				<span aria-hidden="true">&times;</span>
			</button>

		</div>

        <?php

    }
    
    /**
     * Effectively creates the batch by creating the temporary file that the batch is created with
     *
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function create_batch() {

        if ( ! check_admin_referer( 'accessforall_inject_last_modified_date_data', 'nonce' ) ) wp_send_json_error();

        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

        $items = new WP_Query( array(
            'fields' => 'ids',
            'post_type' => array( 'post', 'act_template' ),
            'posts_per_page' => -1,
        ) );

        $post_id_array = array();
        if ( $items->have_posts() ) {
            $post_id_array = $items->posts;
        }

        $tempfile = get_transient( 'accessforall_inject_last_modified_date_data_file' );

        if ( ! $tempfile ) {
            $tempfile = wp_tempnam( 'accessforall_inject_last_modified_date_data' );
        }

        if ( ! $tempfile ) wp_send_json_error();

        if ( ! WP_Filesystem( request_filesystem_credentials( '' ) ) ) wp_send_json_error();

        global $wp_filesystem;
        $success = $wp_filesystem->put_contents( $tempfile, json_encode( $post_id_array ) );

        if ( ! $success ) wp_send_json_error();

        // Temporarily save value so that our next Ajax call can access them
        set_transient( 'accessforall_inject_last_modified_date_data_file', $tempfile, DAY_IN_SECONDS );

        wp_send_json_success( array(
            'items' => $post_id_array,
        ) );

    }

}