<?php

/*
Plugin Name: Rest API for Simple Feature Requests
Description: Rest API to list the available statuses of feature requests and to use register_meta to register the jck_sfr_status field.
Plugin URI: https://github.com/verygoodplugins/FreeFeatureRequests/
Version: 1.0.0
Author: Artiom Sincariov, Jack Arturo
Author URI: https://verygoodplugins.com/
Text Domain: wp-sfr-rest-api
*/

/**
 * @copyright Copyright (c) 2016. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

define( 'WP_SFR_REST_API_VERSION', '1.0.0' );

// deny direct access
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


final class WP_SFR_REST_API {

	/** Singleton *************************************************************/

	/**
	 * @var WP_SFR_REST_API The one true WP_SFR_REST_API
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Main WP_SFR_REST_API Instance
	 *
	 * Insures that only one instance of WP_SFR_REST_API exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 * @return The one true WP_SFR_REST_API
	 */

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_SFR_REST_API ) ) {

			self::$instance = new WP_SFR_REST_API();
			self::$instance->setup_constants();
			self::$instance->rest_apis_init();

		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-sfr-rest-api' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-sfr-rest-api' ), '1.0' );
	}

	/**
	 * Setup plugin constants
	 *
	 * @since 1.0.0
	 * @access private
	 */

	private function setup_constants() {

		if ( ! defined( 'WP_SFR_REST_API_DIR_PATH' ) ) {
			define( 'WP_SFR_REST_API_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'WP_SFR_REST_API_PLUGIN_PATH' ) ) {
			define( 'WP_SFR_REST_API_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WP_SFR_REST_API_DIR_URL' ) ) {
			define( 'WP_SFR_REST_API_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

	}

	/**
	 * Register rest routes.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function rest_apis_init() {

		add_action( 'rest_api_init', function () {

			register_rest_route( 'wp/v2', '/cpt_feature_requests/statuses', array(
				'methods'  => 'GET',
				'callback' => array( __CLASS__, 'get_cpt_feature_requests_statuses' ),
			) );
		} );

		register_meta( 'post', 'jck_sfr_status', array(
			'type'           => 'string',
			'object_subtype' => 'cpt_feature_requests',
			'description'    => 'Feature request status',
			'single'         => true,
			'show_in_rest'   => true,
		) );

		$status_field = 'jck_sfr_status';

		register_rest_field( 'cpt_feature_requests', $status_field, array(
			'get_callback'    => function ( $object ) use ( $status_field ) {
				// Get field as single value from post meta.
				return get_post_meta( $object['id'], $status_field, true );
			},
			'update_callback' => function ( $value, $object ) use ( $status_field ) {
				// Update the field/meta value.
				update_post_meta( $object->ID, $status_field, $value);
			}
		) );
	}

	/**
	 * Get the feature request statuses.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public static function get_cpt_feature_requests_statuses( WP_REST_Request $request) {

		$statuses = jck_sfr_get_statuses();
		return new WP_REST_Response( $statuses );
	}

}


/**
 * The main function responsible for returning the one true Simple Feature Requests API
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $wp_sfr_rest_api = wp_sfr_rest_api(); ?>
 *
 * @return object The one true Simple Feature Requests API
 */

function wp_sfr_rest_api() {

	if ( ! class_exists( 'JCK_Simple_Feature_Requests' ) ) {
		return;
	}

	return WP_SFR_REST_API::instance();

}

add_action( 'plugins_loaded', 'wp_sfr_rest_api', 100 );
