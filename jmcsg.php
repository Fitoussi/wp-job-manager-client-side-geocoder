<?php
/*
Plugin Name: WP Job Manager Client-Side Geocoder
Plugin URI: http://www.geomywp.com
Description: Provides Client-Side geocoder to WP Job Manager plugin to overcome the OVER_QUERY_LIMIT, and other geocoding issues.
Version: 1.1
Author: Eyal Fitoussi
Author URI: http://www.geomywp.com
Requires at least: 4.1
Tested up to: 4.8.1
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
		define( 'JMCSG_VERSION', '1.1' );

		//regsiter scripts
		add_action( 'wp_enqueue_scripts',    array( $this, 'register_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );

		// add region code box only if Jobs Geolocation extension not exists
		if ( ! class_exists( 'GJM_Init' ) ) {
			add_filter( 'job_manager_settings', array( $this, 'admin_settings'), 5 );
		}

		add_filter( 'job_manager_geolocation_region_cctld', array( $this, 'append_region_code_to_geocoder' ), 20 );

		//add hidden address fields to jobs and resume forms in back-end
		add_action( 'job_manager_job_listing_data_end', array( $this, 'hidden_address_fields' ) );
		add_action( 'resume_manager_resume_data_end', 	array( $this, 'hidden_address_fields' ) );
		
		//add hidden address fields to job and resume form in front end
		add_action( 'submit_job_form_job_fields_end', array( $this, 'hidden_address_fields' ) );
		add_filter( 'submit_resume_form_resume_fields_end', array( $this, 'hidden_address_fields' ) );

		//update job and resume location in back-end
		add_action( 'job_manager_save_job_listing', array( $this, 'update_location_admin' ), 15, 2 );
		add_action( 'resume_manager_save_resume', array( $this, 'update_location_admin' ), 15, 2 );
						
		//update job and resume location in front end
		add_action( 'job_manager_update_job_data', array( $this, 'update_location_front_end' ), 10, 2 );
		add_action( 'resume_manager_update_resume_data', array( $this, 'update_location_front_end' ), 10, 2 );
	}

	/**
	 * register scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function register_scripts() {	
		
		//register google maps api
		if ( ! wp_script_is( 'google-maps', 'registered' ) && ! class_exists( 'GEO_my_WP' ) && ! class_exists( 'GJM_Init' ) ) {
			wp_register_script( 'google-maps', esc_url( $this->get_google_maps_api_url() ), array( 'jquery' ), JMCSG_VERSION, true );
		}	

		wp_register_script( 'jmcsg', JMCSG_URL . '/assets/js/jmcgs.min.js', array( 'jquery' ), JMCSG_VERSION, true );
	
		$params = $this->get_api_param();
		$params['is_admin'] = is_admin() ? 1 : 0;

		wp_localize_script( 'jmcsg', 'jmcsgParams', $params );
	}

	/**
	 * Default region settings
	 * @param  [type] $settings [description]
	 * @return [type]           [description]
	 */
	public function admin_settings( $settings ) {

		$settings['job_listings'][1][] = array(
			'name'       => 'jmcsg_region_code',
			'std'        => '',
			'label'      => __( 'Google Maps API Default Region', 'wp-job-manager' ),
			'desc'       => __( 'The default region to be used with Google Maps API when geocoding an address.', 'wp-job-manager' ),
			'attributes' => array()
		);

		return $settings;
	}

	/**
	 * Append region code to WPJM geocoder 
	 * 
	 * @param  [type] $region [description]
	 * @return [type]         [description]
	 */
	public function append_region_code_to_geocoder( $region ) {

		if ( $region == '' ) {

			if ( class_exists( 'GJM_Init' ) ) {

				$gjm_options = get_option( 'gjm_options' );
				$new_region  = $gjm_options['general_settings']['gjm_region'];
			
			} else {

				$new_region = get_option( 'jmcsg_region_code' );
			}
		}
		
		return ! empty( $new_region ) ? $new_region : $region;
	}

	/**
	 * Get langauge and region for Google Maps API
	 * 
	 * @return [type] [description]
	 */
	public function get_api_param() {

		// get options from Jobs Geolocation extension if exists
		if ( class_exists( 'GJM_Init' ) ) {

			$gjm_options = get_option( 'gjm_options' );

			$region   = $gjm_options['general_settings']['gjm_region'];
			$language = $gjm_options['general_settings']['gjm_language'];

		} else {

			$region = get_option( 'jmcsg_region_code' );
			$locale = get_locale();

			if ( $locale ) {
				$language = substr( $locale, 0, 2 );
			}
		}

		return array(
			'language' => ! empty( $language ) ? $language : 'EN',
			'region'   => ! empty( $region )   ? $region : 'US'
		);
	}

	/**
	 * Generate Google Maps API url
	 * @return [type] [description]
	 */
	public function get_google_maps_api_url() {

		$api_url = is_ssl() ? 'https' : 'http' . '://maps.googleapis.com/maps/api/js';

		// Add an API key if available.
		$api_key = get_option( 'job_manager_google_maps_api_key' );

		if ( '' !== $api_key ) {
			$api_url = add_query_arg( 'key', urlencode( $api_key ), $api_url );
		}
		
		$api_params = $this->get_api_param();

		$api_url = add_query_arg( $api_params, $api_url );
		
		return apply_filters( 'jmcgs_google_maps_api_url', $api_url );
	}

	/**
	 * Generate hidden address fields to job and resume forms
	 * 
	 * @param unknown_type $post_id
	 */
	public function hidden_address_fields( $post_id ) {
		
		echo '<div id="jmcsg-geocoder-fields-wrapper" style="display:none !important;clear:both;width:100%;">';
	
		foreach ( $this->address_fields as $af ) {
			echo '<p>';
			//echo '<label for="jmcsg_'.$af.'">'.$af.'</label>';
			echo '<input type="text" name="jmcsg_'.$af.'" id="jmcsg_'.$af.'" class="jmcsg_address_fields" style="width:100%" placeholder="'.$af.'" />';
			echo '</p>';
		}

		// get saved data
		$org_location = get_post_meta( $post_id, '_job_location', true );
		$geocoded 	  = get_post_meta( $post_id, 'geolocated', true ) == 1 ? 'true' : 'false';
	
		echo '<p>';
		//echo '<label for="jmcsg_original_location">Location updated</label>';
		echo '<input type="text" name="jmcsg_location_updated" id="jmcsg_location_update" style="width:100%" class="jmcsg_address_fields" value="" placeholder="Updated" />';
		echo '</p>';

		echo '<p>';
		//echo '<label for="jmcsg-geocoded">Geocoded</label>';
		echo '<input type="text" name="jmcsg_geocoded" id="jmcsg-geocoded" class="jmcsg_address_fields" value="'.$geocoded.'" style="width:100%" placeholder="geocoded"/>';
		echo '</p>';
		echo '</div>';
	
		if ( ! wp_script_is( 'google-maps', 'enqueued' ) ) {
			wp_enqueue_script( 'google-maps' );
		}

		wp_enqueue_script( 'jmcsg' );
	}
	
	/**
	 * delete job's location custom fields
	 * @param unknown_type $job_id
	 */
	public function delete_location_fields( $post_id ) {

		delete_post_meta( $post_id, 'geolocated' );

		foreach ( $this->address_fields as $af ) {
			delete_post_meta( $post_id, 'geolocation_'.$af );
		}
	}

	/**
	 * update job and resume location in back-end
	 * 
	 * @param unknown_type $post_id
	 */
	public function update_location_admin( $post_id, $post ) {
		
		// delete location if address field empty or geocode failed
		if ( empty( $_POST['_job_location'] ) && empty( $_POST['_candidate_location'] ) ) {
			return self::delete_location_fields( $post_id );
		}
		
		// if everything is OK update post location with new data
		if ( $_POST['jmcsg_geocoded'] == 'true' && ! empty( $_POST['jmcsg_location_updated'] ) && ! empty( $_POST['jmcsg_lat'] ) && ! empty( $_POST['jmcsg_long'] ) ) {
			
			//delete old location data
			self::delete_location_fields( $post_id );
		
			//update new data
			update_post_meta( $post_id, 'geolocated', 1 );

			add_filter( 'job_manager_geolocation_enabled', '__return_false' );
			add_filter( 'resume_manager_geolocation_enabled', '__return_false' );

			foreach ( $this->address_fields as $af ) {
				update_post_meta( $post_id, "geolocation_{$af}", sanitize_text_field( $_POST["jmcsg_{$af}"] ) );
			}
		}
	}

	/**
	 * update job and resume location in front-end
	 * 
	 * @param unknown_type $post_id
	 */
	public function update_location_front_end( $post_id, $values ) {
		
		//abort if no address entered
		if ( empty( $values['job']['job_location'] ) && empty( $values['resume_fields']['candidate_location'] ) ) {
			return;
		}

		//abort if coords not exist
		if ( empty( $_POST['jmcsg_lat'] ) || empty( $_POST['jmcsg_long'] ) ) {
			return;
		}

		add_filter( 'job_manager_geolocation_enabled', '__return_false' );
		add_filter( 'resume_manager_geolocation_enabled', '__return_false' );

		//delete old data
		self::delete_location_fields( $post_id );

		//update new location data
		update_post_meta( $post_id, 'geolocated', 1 );
		foreach ( $this->address_fields as $af ) {
			update_post_meta( $post_id, "geolocation_{$af}", sanitize_text_field( $_POST["jmcsg_{$af}"] ) );
		}
	}
}

/**
 * Init JMCSG
 */
function jmcsg_init() {

	//make sure that WP Job Manager is activated
	if ( ! class_exists( 'WP_Job_Manager') ) {
		function jmcsg_deactivated_admin_notice() {
		?>
		<div class="error">
			<p>
				<?php _e( "WP Job Manager Client-side geocoder requires <a href=\"http://wordpress.org/plugins/wp-job-manager/\" target=\"_blank\">WP Job Manager</a> plugin.", "JMCSG" ); ?>
			</p>
		</div>
		<?php       
		}
		return add_action( 'admin_notices', 'jmcsg_deactivated_admin_notice' );
	}
	return new JMCSG();
}
add_action( 'plugins_loaded', 'jmcsg_init' );