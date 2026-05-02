<?php
/**
 * Cleanup on plugin uninstall: remove all options and cached transients.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'gnl_api_key' );
delete_option( 'gnl_api_base_url' );
delete_option( 'gnl_cache_ttl' );

global $wpdb;
$like = $wpdb->esc_like( '_transient_gnl_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
$timeout_like = $wpdb->esc_like( '_transient_timeout_gnl_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $timeout_like ) );
