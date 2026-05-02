<?php
/**
 * Plugin-wide helper functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate a template, allowing themes to override by placing a file at
 * {theme}/gamenight-league/{name}.php.
 *
 * @param string $name Template basename without extension.
 * @return string Absolute path to the template file, or empty string if missing.
 */
function gnl_locate_template( $name ) {
	$name     = sanitize_file_name( $name );
	$relative = 'gamenight-league/' . $name . '.php';
	$found    = locate_template( $relative );
	if ( $found ) {
		return $found;
	}
	$fallback = GNL_PATH . 'templates/' . $name . '.php';
	return file_exists( $fallback ) ? $fallback : '';
}

/**
 * Render a template into a string with extracted data.
 *
 * @param string $name Template basename.
 * @param array  $data Variables exposed to the template.
 * @return string
 */
function gnl_render_template( $name, array $data = array() ) {
	$path = gnl_locate_template( $name );
	if ( ! $path ) {
		return '';
	}
	ob_start();
	extract( $data, EXTR_SKIP );
	include $path;
	return (string) ob_get_clean();
}

/**
 * Render an admin-only inline notice that anonymous visitors will not see.
 *
 * @param string $message
 * @return string
 */
function gnl_admin_only_notice( $message ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	return '<div class="gnl-admin-notice" style="border:1px dashed #c00;padding:8px;margin:8px 0;font-family:sans-serif;font-size:13px;color:#c00;">'
		. esc_html( $message )
		. '</div>';
}

/**
 * Format an ISO-8601 timestamp using the site's timezone and date/time format.
 *
 * @param string $iso Either YYYY-MM-DD or full ISO-8601 with Z suffix.
 * @return string
 */
function gnl_format_datetime( $iso ) {
	if ( empty( $iso ) ) {
		return '';
	}
	try {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $iso ) ) {
			$dt = new DateTimeImmutable( $iso . ' 00:00:00', new DateTimeZone( 'UTC' ) );
			return wp_date( get_option( 'date_format' ), $dt->getTimestamp() );
		}
		$dt = new DateTimeImmutable( $iso );
		return wp_date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$dt->getTimestamp()
		);
	} catch ( \Exception $e ) {
		return $iso;
	}
}
