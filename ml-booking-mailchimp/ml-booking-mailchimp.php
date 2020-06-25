<?php
/*
Plugin Name: Mailchimp for WPCalenderBooking
Description: Integration mailchimp for WP Calender Booking (booking) plugin
Version: 0.1
Author: mlimon
Author URI: http://themepaw.com
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Mailchimp for WP Calender Booking Addons
 * 
 */
class MFWPCBAddons {

	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * Returns an instance of this class. 
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new MFWPCBAddons();
		} 

		return self::$instance;

	} 

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {

		// Checked first is booking plugin is installed
		if ( ! $this->is_installed() ) {
			return false;
        }
        
        add_action( 'wpbc_track_new_booking', [ $this, 'process' ] );
    }
    
    /**
     * Check booking plusin installed or not
     *
     * @return boolean
     */
	public function is_installed() {
		return class_exists( 'Booking_Calendar' );
	}

    /**
     * Process mailchimp integration
     *
     * @param array $data
     * @return void
     */
	public function process( $data ) {

        if( 
            empty( $data ) ||
            !isset( $data['booking_id'] ) || 
            !isset( $data['formdata'] )
        ) {
            return false;
        }
	
        $booking_data = get_form_content ( $data['formdata'], $data['resource_id'] );

        if( 
            !isset( $booking_data['email'] ) || 
            empty( $booking_data['email'] ) || 
            !is_email( $booking_data['email'] ) ||
            !isset( $booking_data['mfw_trigger'] ) ||
            empty( $booking_data['mfw_trigger'] )
        ) {
            return false;
        }

        $this->subscribe( $booking_data['email'] );
    }

    /**
     * Mailchimp Integration
     *
     * @param string $email
     * @return void
     */
    public function subscribe( $email ) {

        $audience_id = apply_filters( 'audience_id', 'b83e304c77' );
        $api_key = apply_filters( 'api_key', '8eeff0a7b73efbbb525087d273343fa6-us14' );
        $data_center = substr($api_key,strpos($api_key,'-')+1);
        $url = 'https://'. $data_center .'.api.mailchimp.com/3.0/lists/'. $audience_id .'/members';
        $auth = base64_encode( 'user:' . $api_key );
        $arr_data = json_encode(array( 
            'email_address' => $email, 
            'status' => 'subscribed' //pass 'subscribed' or 'pending'
        ));
    
        $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => "Basic $auth"
                ),
                'body' => $arr_data,
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log( $response->get_error_message(), 0);
        } else {
            $status_code = wp_remote_retrieve_response_code( $response );
            switch ($status_code) {
                case '200':
                    error_log( $status_code, 0);
                    break;
                case '400':
                    $api_response = json_decode( wp_remote_retrieve_body( $response ), true );
                    error_log( $api_response['title'], 0);
                    break;
                default:
                    error_log( 'Something went wrong. Please try again.', 0);
                    break;
            }
        }
    }

} 
add_action( 'plugins_loaded', array( 'MFWPCBAddons', 'get_instance' ) );