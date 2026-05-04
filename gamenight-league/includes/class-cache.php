<?php
/**
 * Thin transient wrapper for caching GameNight API responses.
 */

namespace GameNight\League;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cache {

	const PREFIX = 'gnl_';

	/**
	 * Build a stable transient key for a logical (path, args) tuple.
	 */
	public function key( $path, array $args = array() ) {
		ksort( $args );
		return self::PREFIX . md5( $path . '|' . wp_json_encode( $args ) );
	}

	public function get( $key ) {
		return get_transient( $key );
	}

	public function set( $key, $value, $ttl ) {
		$ttl = max( 1, (int) $ttl );
		set_transient( $key, $value, $ttl );
	}

	public function delete( $key ) {
		delete_transient( $key );
	}

	/**
	 * Invalidate every transient created by this plugin.
	 *
	 * Used after writes so visitors see fresh counts immediately. Direct DB
	 * query because there is no native API for "delete transients by prefix".
	 */
	public function flush_all() {
		global $wpdb;
		$like = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		// Direct DB query: WordPress has no native API for "delete transients by
		// prefix"; iterating get_option() lists would be far slower. No caching:
		// this IS the cache invalidation path, so wp_cache_* would be circular.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$timeout_like = $wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $timeout_like ) );
	}
}
