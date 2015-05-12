<?php
/*
Plugin Name: WP Job Manager Client-Side Geocoder
Plugin URI: http://www.geomywp.com
Description: Add client-side geocoder to Wp Job Manager plugin to overcome the OVER_QUERY_LIMIT issue
Version: 1.0.1
Author: Eyal Fitoussi
Author URI: http://www.geomywp.com
Requires at least: 4.1
Tested up to: 4.2.2
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * JBCSG class.
 */
class JMCSG {

	/**
	 * Address fields to geocode
	 * @var array
	 */
	private $address_fields = array( 
		'street_number', 
		'street',
		'city',
		'state_short',
		'state_long',
		'postcode',
		'country_short',
		'country_long',
		'lat',
		'long',
		'formatted_address' 
	);

	/**
	 * Construct
	 * @since 1.0
	 */
	public function __construct() {
		
		//define constants
		define(	'JMCSG_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'JMCSG_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JMCSG_VERSION', '1.0.1' );

		//regsiter scripts
		add_action( 'wp_enqueue_scripts',    array( $this, 'register_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		
		//add hidden address fields to jobs and resume forms in back-end
		add_action( 'job_manager_job_listing_data_end', array( $this, 'hidden_address_fields_admin' ) );
		add_action( 'resume_manager_resume_data_end', 	array( $this, 'hidden_address_fields_admin' ) );
		
		//update job and resume location in back-end
		add_action( 'save_post', array( $this, 'update_job_location_admin'    ) );
		add_action( 'save_post', array( $this, 'update_resume_location_admin' ) );
		
		//add hidden address fields to job and resume form in front end
		add_action( 'submit_job_form_job_fields_end', array( $this, 'hidden_address_fields_front_end' ) );
		add_filter( 'submit_resume_form_resume_fields_end', array( $this, 'hidden_address_fields_front_end' ) );
				
		//update job and resume location in front end
		add_action( 'job_manager_update_job_data', 		 array( $this, 'update_job_location_front'    ), 10, 2 );
		add_action( 'resume_manager_update_resume_data', array( $this, 'update_resume_location_front' ), 10, 2 );
	}
		
	/**
	 * register scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_scripts() {	
		//register google maps api
		if ( !wp_script_is( 'google-maps', 'registered' ) && !class_exists( 'GEO_my_WP' ) ) {
			wp_register_script( 'google-maps', ( is_ssl() ? 'https' : 'http' ) . '://maps.googleapis.com/maps/api/js?sensor=false', array( 'jquery' ), false );
		}	
		if ( is_admin() ) {
			wp_register_script( 'jmcsg-geocoder', JMCSG_URL . '/assets/js/geocoder.admin.min.js', array( 'jquery' ), JMCSG_VERSION, true );
		} else {
			wp_register_script( 'jmcsg-geocoder', JMCSG_URL . '/assets/js/geocoder.front.min.js', array( 'jquery' ), JMCSG_VERSION, true );
		}	
	}
	
	/**
	 * add hidden address fields to job and resume form in admin dashboard
	 * @param unknown_type $post_id
	 */
	function hidden_address_fields_admin( $post_id ) {
		
		echo '<div id="jmcsg-geocoder-fields-wrapper" style="display:none;clear:both;width:100%;">';
	
		foreach ( $this->address_fields as $af ) {
			echo "<p>";
			echo "<label for=\"jmcsg_{$af}\">{$af}</label>";
			echo "<input type=\"text\" name=\"jmcsg_{$af}\" id=\"jmcsg_{$af}\" class=\"jmcsg_address_fields\" style=\"width:100%\" />";
			echo "</p>";
		}

		$org_location = get_post_meta( $post_id, '_job_location', true );
		$geocoded 	  = ( get_post_meta( $post_id, 'geolocated', true ) == 1 ) ? 'true' : 'false';
	
		echo "<p>";
		echo "<label for=\"jmcsg_original_location\">Original Address</label>";
		echo '<input type="text" name="jmcsg_original_location" id="jmcsg_original_location" style=\"width:100%\" class="jmcsg_address_fields" value="'.esc_attr( $org_location ).'" />';
		echo "</p>";

		echo "<p>";
		echo "<label for=\"jmcsg-geocoded\">Geocoded</label>";
		echo "<input type=\"text\" name=\"jmcsg_geocoded\" id=\"jmcsg-geocoded\" class=\"jmcsg_address_fields\" value=\"{$geocoded}\" style=\"width:100%\" />";
		echo "</p>";
		echo '</div>';
	
		if ( !wp_script_is( 'google-maps', 'enqueued' ) ) {
			wp_enqueue_script( 'google-maps' );
		}
		wp_enqueue_script( 'jmcsg-geocoder' );
	}
	
	/**
	 * update job location custom fields in back-end
	 * @param unknown_type $post_id
	 */
	function update_job_location_admin( $post_id ) {
		global $post;
	
		if ( !isset($_POST['post_type']) || $_POST['post_type'] != 'job_listing' )
			return;
	
		// verify nonce //
		if ( empty( $_POST['job_manager_nonce'] ) || ! wp_verify_nonce( $_POST['job_manager_nonce'], 'save_meta_data' ) )
			return;
	
		if ( false !== wp_is_post_revision( $post_id ) )
			return;
	
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
	
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
	
		//delete location if address field empty or geocode failed
		if ( empty( $_POST['_job_location'] ) || empty( $_POST['jmcsg_geocoded'] ) )
			return self::delete_location_fields( $post_id );
	
		//if everything is OK update post location with new data
		if ( $_POST['jmcsg_geocoded'] == 'true' && $_POST['_job_location'] != $_POST['jmcsg_original_location'] && !empty( $_POST['jmcsg_lat'] ) && !empty( $_POST['jmcsg_long'] ) ) {
			
			//delete old location data
			self::delete_location_fields( $post_id);
	
			//update new data
			update_post_meta( $post_id, 'geolocated', 1 );
			foreach ( $this->address_fields as $af ) {
				update_post_meta( $post_id, "geolocation_{$af}", sanitize_text_field( $_POST["jmcsg_{$af}"] ) );
			}
		}
	}
	
	/**
	 * Update resume location in back-end
	 * @param unknown_type $post_id
	 */
	function update_resume_location_admin($post_id) {
		global $wpdb, $post;
	
		//varify that its resume page 
		if (  !isset($_POST['post_type']) || $_POST['post_type'] != 'resume' )
			return;
	
		if ( false !== wp_is_post_revision( $post_id ) )
			return;
	
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
	
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;
			
		//delete location if address field empty or geocoding failed
		if ( empty( $_POST['_candidate_location'] ) || empty( $_POST['jmcsg_geocoded'] ) )
			return self::delete_location_fields( $post_id );
		
		//update location data if everything is OK
		if ( $_POST['jmcsg_geocoded'] == 'true' && $_POST['_candidate_location'] != $_POST['jmcsg_original_location'] && !empty( $_POST['jmcsg_lat'] ) && !empty( $_POST['jmcsg_long'] ) ) {
			
			//delete old location data
			self::delete_location_fields( $post_id);
		
			//update new data
			update_post_meta( $post_id, 'geolocated', 1 );
			foreach ( $this->address_fields as $af ) {
				update_post_meta( $post_id, "geolocation_{$af}", sanitize_text_field( $_POST["jmcsg_{$af}"] ) );
			}
		}		
	}
	
	/**
	 * add hidden address fields to job and resume form in front-end
	 * @param unknown_type $post_id
	 */
	function hidden_address_fields_front_end() {

		echo '<div id="jmcsg-geocoder-fields-wrapper" style="display:none">';
	
		foreach ( $this->address_fields as $af ) {
			echo '<p>';
			echo "<label for=\"jmcsg_{$af}\">{$af}</label>";
			echo "<input type=\"text\" name=\"jmcsg_{$af}\" id=\"jmcsg_{$af}\" class=\"jmcsg-address-fields\" style=\"width:100%\" />";
			echo '</p>';
		}
		echo '</div>';
		
		if ( !wp_script_is( 'google-maps', 'enqueued' ) ) {
			wp_enqueue_script( 'google-maps' );
		}
		wp_enqueue_script( 'jmcsg-geocoder' );
	}
	
	/**
	 * update job location custom fields in front-end
	 * @param unknown_type $post_id
	 */
	function update_job_location_front( $post_id, $values ) {

		//delete old data
		self::delete_location_fields( $post_id );
	
		//abort if no address entered or if geocoding failed
		if ( empty( $values['job']['job_location'] ) || empty( $_POST['jmcsg_lat'] ) || empty( $_POST['jmcsg_long'] ) )
			return;
	
		//update new location data
		update_post_meta( $post_id, 'geolocated', 1 );
		foreach ( $this->address_fields as $af ) {
			update_post_meta( $post_id, "geolocation_{$af}", sanitize_text_field( $_POST["jmcsg_{$af}"] ) );
		}
	}
	
	/**
	 * update resume location custom fields in front-end
	 * @param unknown_type $post_id
	 */
	function update_resume_location_front( $post_id, $values ) {
		
		//delete old data
		self::delete_location_fields( $post_id );
	
		//abort if no address entered 
		if ( empty( $values['resume_fields']['candidate_location'] ) || empty( $_POST['jmcsg_lat'] ) || empty( $_POST['jmcsg_long'] ) )
			return;
	
		//update new location
		update_post_meta( $post_id, 'geolocated', 1 );
		foreach ( $this->address_fields as $af ) {
			update_post_meta( $post_id, "geolocation_{$af}", sanitize_text_field( $_POST["jmcsg_{$af}"] ) );
		}
	
	}
	
	/**
	 * delete job's location custom fields
	 * @param unknown_type $job_id
	 */
	function delete_location_fields( $post_id ) {

		delete_post_meta( $post_id, 'geolocated' );
		foreach ( $this->address_fields as $af ) {
			delete_post_meta( $post_id, "geolocation_{$af}" );
		}
	}
}

/**
 * Init JMCSG
 */
function jmcsg_init() {

	//make sure that WP Job Manager is activated
	if ( !class_exists( 'WP_Job_Manager') ) {
		function jmcsg_deactivated_admin_notice() {
		?>
		<div class="error">
			<p>
				<?php _e( "WP Job Manager Client-side geocoder requires <a href=\"http://wordpress.org/plugins/wp-job-manager/\" target=\"_blank\">WP Job Manager</a> plugin version 1.10 or higher in order to work.", "JMCSG" ); ?>
			</p>
		</div>
		<?php       
		}
		return add_action( 'admin_notices', 'jmcsg_deactivated_admin_notice' );
	}
	return new JMCSG();
}
add_action( 'plugins_loaded', 'jmcsg_init' );