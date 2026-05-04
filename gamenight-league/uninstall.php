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
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- top-level uninstall script, scoped to this file.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WP has no API for "delete transients by prefix"; this is the uninstall path, so cache APIs are not applicable.
$gnl_like = $wpdb->esc_like( '_transient_gnl_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $gnl_like ) );
$gnl_timeout_like = $wpdb->esc_like( '_transient_timeout_gnl_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $gnl_timeout_like ) );
// phpcs:enable
