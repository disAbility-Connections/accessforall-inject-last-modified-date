<?php
/**
 * Batch class for creating a Gradebook for each Course
 *
 * @since {{VERSION}}
 *
 * @package AccessForAll_Inject_Last_Modified_Date
 * @subpackage AccessForAll_Inject_Last_Modified_Date/core/batch
 */

defined( 'ABSPATH' ) || die();

if ( class_exists( 'WP_Batch' ) ) {

    /**
     * Class AccessForAll_Inject_Last_Modified_Date_Batch
     */
    class AccessForAll_Inject_Last_Modified_Date_Batch extends WP_Batch {

        /**
         * Unique identifier of each batch
         * @var string
         */
        public $id = 'accessforall_inject_last_modified_date';

        /**
         * Describe the batch
         * @var string
         */
        public $title;

        /**
         * Our Batch items get dynamically added via a Temp File that is created on our Ajax Callback
         * If the transient holding the file name has expired, we will assume the temp file has been deleted by now
         * The import, while it can be resumed, should be completed in one go if possible due to this
         *
         * @return  [void]
         */
        public function setup() {

            // Put it here for translatability
            $this->title = __( 'Fluency Matters Gradebook Migration', 'accessforall-inject-last-modified-date' );

            $items_file_name = get_transient( 'accessforall_inject_last_modified_date_data_file' );

            if ( ! $items_file_name ) return;

            $items = file_get_contents( $items_file_name );
            $items = json_decode( stripslashes( $items ), true );

            if ( ! $items ) $items = array();

            foreach ( $items as $post_id ) {

                $this->push( new WP_Batch_Item( $post_id, array( 'post_id' => $post_id ) ) );

            }

        }

        /**
         * Handles processing of batch item. One at a time.
         *
         * In order to work it correctly you must return values as follows:
         *
         * - TRUE - If the item was processed successfully.
         * - WP_Error instance - If there was an error. Add message to display it in the admin area.
         *
         * @param WP_Batch_Item $item
         *
         * @return bool|\WP_Error
         */
        public function process( $item ) {

            $post = get_post( $item->data['post_id'] );

            add_filter( 'wp_insert_post_data', array( $this, 'fix_modified_timestamp' ), 10, 2 );

            $post_content = preg_replace( '/<\/div>{1}/', "\n" . '<b>Last Surveyed Date:</b> [accessforall_last_modified_date]</div>', $post->post_content );

            $post_id = wp_insert_post( array(
                'ID' => $post->ID,
                'post_type' => $post->post_type,
                'post_title' => $post->post_title,
                'post_status' => $post->post_status,
                'post_content' => $post_content,
                'post_modified' => ( $post->post_modified ) ? $post->post_modified : false,
                'post_modified_gmt' => ( $post->post_modified_gmt ) ? $post->post_modified_gmt : false,
            ), true );

            remove_filter( 'wp_insert_post_data', array( $this, 'fix_modified_timestamp' ), 10, 2 );

            if ( is_wp_error( $post_id ) ) {
                $errors = implode( ';', $post_id->get_error_messages() );
                return new WP_Error( 302, $errors );
            }

            // Return true if the item processing is successful.
            return true;

        }

        /**
         * The way WordPress is explicitly programmed to always update Last Modified when a Post is being programmatically updated is getting in the way
         *
         * @param   array  $update_data  The data WordPress wants to use to update the Post
         * @param   array  $post_args    The data we told WordPress to use to update the Post
         *
         * @access  public
         * @since   {{VERSION}}
         * @return  array                The data that WordPress will use to update the Post
         */
        public function fix_modified_timestamp( $update_data, $post_args ) {

            $update_data['post_modified'] = ( isset( $post_args['post_modified'] ) && $post_args['post_modified'] ) ? $post_args['post_modified'] : $update_data['post_modified'];
            
            $update_data['post_modified_gmt'] = ( isset( $post_args['post_modified_gmt'] ) && $post_args['post_modified_gmt'] ) ? $post_args['post_modified_gmt'] : $update_data['post_modified_gmt'];

            return $update_data;

        }

        /**
         * Called when specific process is finished (all items were processed).
         * This method can be overriden in the process class.
         * @return void
         */
        public function finish() {

            // Delete the file that we stored our Batch Data in

            $filename = get_transient( 'accessforall_inject_last_modified_date_data_file' );

            if ( ! $filename ) return;

            if ( ! WP_Filesystem( request_filesystem_credentials( '' ) ) ) return;

            global $wp_filesystem;
            $success = $wp_filesystem->delete( $filename );

            delete_transient( 'accessforall_inject_last_modified_date_data_file' );

        }

    }

}